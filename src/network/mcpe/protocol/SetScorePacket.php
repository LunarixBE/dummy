<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use function count;

class SetScorePacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::SET_SCORE_PACKET;

	public const TYPE_CHANGE = 0;
	public const TYPE_REMOVE = 1;

	public int $type;
	/** @var ScorePacketEntry[] */
	public array $entries = [];

	/**
	 * @generate-create-func
	 * @param ScorePacketEntry[] $entries
	 */
	public static function create(int $type, array $entries) : self{
		$result = new self();
		$result->type = $type;
		$result->entries = $entries;
		return $result;
	}

	/** >= PROTOCOL_1_26_40 action ids, sent alongside the numeric entry type */
	private const ACTION_IDS_1_26_40 = [
		ScorePacketEntry::TYPE_REMOVE => "remove",
		ScorePacketEntry::TYPE_PLAYER => "changeplayer",
		ScorePacketEntry::TYPE_ENTITY => "changeentity",
		ScorePacketEntry::TYPE_FAKE_PLAYER => "changefakeplayer",
	];

	protected function decodePayload(PacketSerializer $in) : void{
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->type = self::TYPE_CHANGE;
			for($i = 0, $i2 = $in->getUnsignedVarInt(); $i < $i2; ++$i){
				$entry = new ScorePacketEntry();
				$entry->type = $in->getUnsignedVarInt();
				$in->getString(); //action id
				switch($entry->type){
					case ScorePacketEntry::TYPE_REMOVE:
						$this->type = self::TYPE_REMOVE;
						$entry->scoreboardId = $in->getVarLong();
						$entry->objectiveName = $in->readOptional(fn() => $in->getString()) ?? "";
						break;
					case ScorePacketEntry::TYPE_PLAYER:
					case ScorePacketEntry::TYPE_ENTITY:
						$entry->scoreboardId = $in->getVarLong();
						$entry->objectiveName = $in->getString();
						$entry->score = $in->getLInt();
						$entry->actorUniqueId = $in->getActorUniqueId();
						break;
					case ScorePacketEntry::TYPE_FAKE_PLAYER:
						$entry->scoreboardId = $in->getVarLong();
						$entry->objectiveName = $in->getString();
						$entry->score = $in->getLInt();
						$entry->customName = $in->getString();
						break;
					default:
						throw new PacketDecodeException("Unknown entry type $entry->type");
				}
				$this->entries[] = $entry;
			}
			return;
		}

		$this->type = $in->getByte();
		for($i = 0, $i2 = $in->getUnsignedVarInt(); $i < $i2; ++$i){
			$entry = new ScorePacketEntry();
			$entry->scoreboardId = $in->getVarLong();
			$entry->objectiveName = $in->getString();
			$entry->score = $in->getLInt();
			if($this->type !== self::TYPE_REMOVE){
				$entry->type = $in->getByte();
				switch($entry->type){
					case ScorePacketEntry::TYPE_PLAYER:
					case ScorePacketEntry::TYPE_ENTITY:
						$entry->actorUniqueId = $in->getActorUniqueId();
						break;
					case ScorePacketEntry::TYPE_FAKE_PLAYER:
						$entry->customName = $in->getString();
						break;
					default:
						throw new PacketDecodeException("Unknown entry type $entry->type");
				}
			}
			$this->entries[] = $entry;
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_40){
			$out->putUnsignedVarInt(count($this->entries));
			foreach($this->entries as $entry){
				$entryType = $this->type === self::TYPE_REMOVE ? ScorePacketEntry::TYPE_REMOVE : $entry->type;
				$out->putUnsignedVarInt($entryType);
				$out->putString(self::ACTION_IDS_1_26_40[$entryType] ?? throw new \InvalidArgumentException("Unknown entry type $entryType"));
				$out->putVarLong($entry->scoreboardId);
				switch($entryType){
					case ScorePacketEntry::TYPE_REMOVE:
						$out->writeOptional($entry->objectiveName, fn(string $v) => $out->putString($v));
						break;
					case ScorePacketEntry::TYPE_PLAYER:
					case ScorePacketEntry::TYPE_ENTITY:
						$out->putString($entry->objectiveName);
						$out->putLInt($entry->score);
						$out->putActorUniqueId($entry->actorUniqueId);
						break;
					case ScorePacketEntry::TYPE_FAKE_PLAYER:
						$out->putString($entry->objectiveName);
						$out->putLInt($entry->score);
						$out->putString($entry->customName);
						break;
				}
			}
			return;
		}

		$out->putByte($this->type);
		$out->putUnsignedVarInt(count($this->entries));
		foreach($this->entries as $entry){
			$out->putVarLong($entry->scoreboardId);
			$out->putString($entry->objectiveName);
			$out->putLInt($entry->score);
			if($this->type !== self::TYPE_REMOVE){
				$out->putByte($entry->type);
				switch($entry->type){
					case ScorePacketEntry::TYPE_PLAYER:
					case ScorePacketEntry::TYPE_ENTITY:
						$out->putActorUniqueId($entry->actorUniqueId);
						break;
					case ScorePacketEntry::TYPE_FAKE_PLAYER:
						$out->putString($entry->customName);
						break;
					default:
						throw new \InvalidArgumentException("Unknown entry type $entry->type");
				}
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetScore($this);
	}
}
