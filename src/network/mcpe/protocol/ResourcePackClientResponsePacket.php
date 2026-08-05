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
use function count;

class ResourcePackClientResponsePacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_CLIENT_RESPONSE_PACKET;

	public const STATUS_REFUSED = 1;
	public const STATUS_SEND_PACKS = 2;
	public const STATUS_HAVE_ALL_PACKS = 3;
	public const STATUS_COMPLETED = 4;

	public int $status;
	/** @var string[] */
	public array $packIds = [];

	/**
	 * @generate-create-func
	 * @param string[] $packIds
	 */
	public static function create(int $status, array $packIds) : self{
		$result = new self();
		$result->status = $status;
		$result->packIds = $packIds;
		return $result;
	}

	/** >= PROTOCOL_1_26_40 renumbered these 1..4 -> 0..3; the constants keep the old numbering */
	private function getStatusId() : string{
		return match($this->status){
			self::STATUS_REFUSED => "cancel",
			self::STATUS_SEND_PACKS => "downloading",
			self::STATUS_HAVE_ALL_PACKS => "downloadingfinished",
			self::STATUS_COMPLETED => "resourcepackstackfinished",
			default => throw new \InvalidArgumentException("Unknown status " . $this->status)
		};
	}

	protected function decodePayload(PacketSerializer $in) : void{
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->status = $in->getUnsignedVarInt() + 1;
			$in->getString(); //string form of the status
			$this->packIds = [];
			if($this->status === self::STATUS_SEND_PACKS){
				for($i = 0, $entryCount = $in->getUnsignedVarInt(); $i < $entryCount; ++$i){
					$this->packIds[] = $in->getString();
				}
			}
			return;
		}

		$this->status = $in->getByte();
		$entryCount = $in->getLShort();
		$this->packIds = [];
		while($entryCount-- > 0){
			$this->packIds[] = $in->getString();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_40){
			$out->putUnsignedVarInt($this->status - 1);
			$out->putString($this->getStatusId());
			if($this->status === self::STATUS_SEND_PACKS){
				$out->putUnsignedVarInt(count($this->packIds));
				foreach($this->packIds as $id){
					$out->putString($id);
				}
			}
			return;
		}

		$out->putByte($this->status);
		$out->putLShort(count($this->packIds));
		foreach($this->packIds as $id){
			$out->putString($id);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleResourcePackClientResponse($this);
	}
}
