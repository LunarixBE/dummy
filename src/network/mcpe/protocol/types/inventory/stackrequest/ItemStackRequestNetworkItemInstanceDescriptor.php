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

namespace pocketmine\network\mcpe\protocol\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;

/**
 * >= PROTOCOL_1_26_40 item description used by stack request actions: a recipe ingredient plus block runtime ID and
 * raw extra data, instead of a full item stack.
 */
final class ItemStackRequestNetworkItemInstanceDescriptor{

	public function __construct(
		private RecipeIngredient $ingredient,
		private int $blockRuntimeId,
		private string $rawExtraData
	){}

	public function getIngredient() : RecipeIngredient{ return $this->ingredient; }

	public function getBlockRuntimeId() : int{ return $this->blockRuntimeId; }

	public function getRawExtraData() : string{ return $this->rawExtraData; }

	public static function read(PacketSerializer $in) : self{
		$ingredient = $in->getRecipeIngredient();
		$blockRuntimeId = $in->getUnsignedVarInt();
		$rawExtraData = $in->getString();
		return new self($ingredient, $blockRuntimeId, $rawExtraData);
	}

	public function write(PacketSerializer $out) : void{
		$out->putRecipeIngredient($this->ingredient);
		$out->putUnsignedVarInt($this->blockRuntimeId);
		$out->putString($this->rawExtraData);
	}
}
