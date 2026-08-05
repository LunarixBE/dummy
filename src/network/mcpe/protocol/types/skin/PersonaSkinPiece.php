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

namespace pocketmine\network\mcpe\protocol\types\skin;

use function array_flip;

final class PersonaSkinPiece{

	/**
	 * >= PROTOCOL_1_26_40, where the piece type is a numeric ID. The string form stays canonical - it's what the login
	 * client data provides.
	 *
	 * @var int[]
	 * @phpstan-var array<string, int>
	 */
	public const PIECE_TYPE_IDS = [
		"persona_skeleton" => 0,
		"persona_body" => 1,
		"persona_skin" => 2,
		"persona_bottom" => 3,
		"persona_feet" => 4,
		"persona_dress" => 5,
		"persona_top" => 6,
		"persona_high_pants" => 7,
		"persona_hand" => 8,
		"persona_outerwear" => 9,
		"persona_facial_hair" => 10,
		"persona_mouth" => 11,
		"persona_eyes" => 12,
		"persona_hair" => 13,
		"persona_hood" => 14,
		"persona_back" => 15,
		"persona_face_accessory" => 16,
		"persona_head" => 17,
		"persona_legs" => 18,
		"persona_left_leg" => 19,
		"persona_right_leg" => 20,
		"persona_arms" => 21,
		"persona_left_arm" => 22,
		"persona_right_arm" => 23,
		"persona_capes" => 24,
		"persona_classic_skin" => 25,
		"persona_emote" => 26,
	];

	/** unknown piece types map to 0 - dropping the login over one isn't worth it */
	public static function pieceTypeToId(string $pieceType) : int{
		return self::PIECE_TYPE_IDS[$pieceType] ?? 0;
	}

	public static function pieceTypeFromId(int $id) : string{
		/** @phpstan-var array<int, string>|null $reverse */
		static $reverse = null;
		$reverse ??= array_flip(self::PIECE_TYPE_IDS);

		return $reverse[$id] ?? "persona_skeleton";
	}

	public const PIECE_TYPE_PERSONA_BODY = "persona_body";
	public const PIECE_TYPE_PERSONA_BOTTOM = "persona_bottom";
	public const PIECE_TYPE_PERSONA_EYES = "persona_eyes";
	public const PIECE_TYPE_PERSONA_FACIAL_HAIR = "persona_facial_hair";
	public const PIECE_TYPE_PERSONA_FEET = "persona_feet";
	public const PIECE_TYPE_PERSONA_HAIR = "persona_hair";
	public const PIECE_TYPE_PERSONA_MOUTH = "persona_mouth";
	public const PIECE_TYPE_PERSONA_SKELETON = "persona_skeleton";
	public const PIECE_TYPE_PERSONA_SKIN = "persona_skin";
	public const PIECE_TYPE_PERSONA_TOP = "persona_top";

	public function __construct(
		private string $pieceId,
		private string $pieceType,
		private string $packId,
		private bool $isDefaultPiece,
		private string $productId
	){}

	public function getPieceId() : string{
		return $this->pieceId;
	}

	public function getPieceType() : string{
		return $this->pieceType;
	}

	public function getPackId() : string{
		return $this->packId;
	}

	public function isDefaultPiece() : bool{
		return $this->isDefaultPiece;
	}

	public function getProductId() : string{
		return $this->productId;
	}
}
