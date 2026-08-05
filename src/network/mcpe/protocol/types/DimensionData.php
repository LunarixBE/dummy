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

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class DimensionData{

	public function __construct(
		private int $maxHeight,
		private int $minHeight,
		private int $generator,
		private int $dimensionType = DimensionIds::OVERWORLD,
		/** >= PROTOCOL_1_26_40 */
		private ?UuidInterface $packId = null
	){}

	public function getMaxHeight() : int{ return $this->maxHeight; }

	public function getMinHeight() : int{ return $this->minHeight; }

	public function getGenerator() : int{ return $this->generator; }

	public function getDimensionType() : int{ return $this->dimensionType; }

	/** >= PROTOCOL_1_26_40 */
	public function getPackId() : ?UuidInterface{ return $this->packId; }

	public static function read(PacketSerializer $in, ?int $protocolId = null) : self{
		$protocolId ??= $in->getProtocolId();
		$maxHeight = $in->getVarInt();
		$minHeight = $in->getVarInt();
		$generator = $in->getVarInt();
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			$dimensionType = $in->getVarInt();
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$packId = $in->getUUID();
		}

		return new self($maxHeight, $minHeight, $generator, $dimensionType ?? DimensionIds::OVERWORLD, $packId ?? null);
	}

	public function write(PacketSerializer $out, ?int $protocolId = null) : void{
		$protocolId ??= $out->getProtocolId();
		$out->putVarInt($this->maxHeight);
		$out->putVarInt($this->minHeight);
		$out->putVarInt($this->generator);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			$out->putVarInt($this->dimensionType);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$out->putUUID($this->packId ?? Uuid::fromString(Uuid::NIL));
		}
	}
}
