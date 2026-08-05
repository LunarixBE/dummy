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

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class MapTrackedObject{
	public const TYPE_ENTITY = 0;
	public const TYPE_BLOCK = 1;
	/** >= PROTOCOL_1_26_40 */
	public const TYPE_BLOCK_ENTITY = 1;
	public const TYPE_OTHER = 2;

	public int $type;

	/** @var int|null Only set if is TYPE_ENTITY */
	public ?int $actorUniqueId = null;

	/** Only set if is TYPE_BLOCK */
	public ?BlockPosition $blockPosition = null;

	public static function read(PacketSerializer $in) : self{
		$result = new self();
		$result->type = $in->getLInt();
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_40){
			$result->actorUniqueId = $in->readOptional(fn() => $in->getActorUniqueId());
			$result->blockPosition = $in->readOptional(fn() => $in->getBlockPosition());
			return $result;
		}

		if($result->type === self::TYPE_BLOCK){
			$result->blockPosition = $in->getBlockPosition();
		}elseif($result->type === self::TYPE_ENTITY){
			$result->actorUniqueId = $in->getActorUniqueId();
		}else{
			throw new PacketDecodeException("Unknown map object type $result->type");
		}
		return $result;
	}

	public function write(PacketSerializer $out) : void{
		$out->putLInt($this->type);
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_40){
			$out->writeOptional($this->actorUniqueId, fn(int $v) => $out->putActorUniqueId($v));
			$out->writeOptional($this->blockPosition, fn(BlockPosition $v) => $out->putBlockPosition($v));
			return;
		}

		if($this->type === self::TYPE_BLOCK){
			$out->putBlockPosition($this->blockPosition ?? throw new \InvalidArgumentException("blockPosition must be set for TYPE_BLOCK"));
		}elseif($this->type === self::TYPE_ENTITY){
			$out->putActorUniqueId($this->actorUniqueId ?? throw new \InvalidArgumentException("actorUniqueId must be set for TYPE_ENTITY"));
		}else{
			throw new \InvalidArgumentException("Unknown map object type $this->type");
		}
	}
}
