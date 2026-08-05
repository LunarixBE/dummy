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

namespace pocketmine\network\mcpe\protocol\types\recipe;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;

/**
 * >= PROTOCOL_1_26_40 replacement for {@link StringIdMetaItemDescriptor} - aux value is a signed varint, and it took
 * over descriptor type ID 1.
 */
final class NameItemDescriptor implements ItemDescriptor{
	use GetTypeIdFromConstTrait;

	public const ID = ItemDescriptorType::NAME;

	public function __construct(
		private string $name,
		private int $auxValue
	){}

	public function getName() : string{ return $this->name; }

	public function getAuxValue() : int{ return $this->auxValue; }

	public static function read(PacketSerializer $in) : self{
		$name = $in->getString();
		$auxValue = $in->getVarInt();

		return new self($name, $auxValue);
	}

	public function write(PacketSerializer $out) : void{
		$out->putString($this->name);
		$out->putVarInt($this->auxValue);
	}
}
