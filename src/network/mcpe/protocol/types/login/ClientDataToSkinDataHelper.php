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

namespace pocketmine\network\mcpe\protocol\types\login;

use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use function array_map;
use function base64_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

final class ClientDataToSkinDataHelper{

	/**
	 * @throws \InvalidArgumentException
	 */
	private static function safeB64Decode(string $base64, string $context) : string{
		$result = base64_decode($base64, true);
		if($result === false){
			throw new \InvalidArgumentException("$context: Malformed base64, cannot be decoded");
		}
		return $result;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public static function fromClientData(ClientData $clientData) : SkinData{
		$skinBytes = self::safeB64Decode($clientData->SkinData, "SkinData");
		$capeBytes = self::safeB64Decode($clientData->CapeData, "CapeData");
		$resourcePatch = $clientData->SkinResourcePatch !== "" ?
			self::safeB64Decode($clientData->SkinResourcePatch, "SkinResourcePatch") :
			json_encode(["geometry" => ["default" => $clientData->SkinGeometryName !== "" ? $clientData->SkinGeometryName : "geometry.humanoid.custom"]], JSON_THROW_ON_ERROR);
		$geometryData = $clientData->SkinGeometryData !== "" ?
			self::safeB64Decode($clientData->SkinGeometryData, "SkinGeometryData") :
			self::safeB64Decode($clientData->SkinGeometry, "SkinGeometry");
		$skinImage = $clientData->SkinImageHeight > 0 && $clientData->SkinImageWidth > 0 ?
			new SkinImage($clientData->SkinImageHeight, $clientData->SkinImageWidth, $skinBytes) :
			SkinImage::fromLegacy($skinBytes);
		$capeImage = $capeBytes !== "" ?
			($clientData->CapeImageHeight > 0 && $clientData->CapeImageWidth > 0 ?
				new SkinImage($clientData->CapeImageHeight, $clientData->CapeImageWidth, $capeBytes) :
				SkinImage::fromLegacy($capeBytes)) :
			new SkinImage(0, 0, "");

		/** @var SkinAnimation[] $animations */
		$animations = [];
		foreach($clientData->AnimatedImageData as $k => $animation){
			$animations[] = new SkinAnimation(
				new SkinImage(
					$animation->ImageHeight,
					$animation->ImageWidth,
					self::safeB64Decode($animation->Image, "AnimatedImageData.$k.Image")
				),
				$animation->Type,
				$animation->Frames,
				$animation->AnimationExpression ?? 0
			);
		}
		return new SkinData(
			$clientData->SkinId,
			$clientData->PlayFabId ?? "",
			$resourcePatch,
			$skinImage,
			$animations,
			$capeImage,
			$geometryData,
			self::safeB64Decode($clientData->SkinGeometryDataEngineVersion ?? "", "SkinGeometryDataEngineVersion"), //yes, they actually base64'd the version!
			self::safeB64Decode($clientData->SkinAnimationData, "SkinAnimationData"),
			$clientData->CapeId,
			null,
			$clientData->ArmSize,
			$clientData->SkinColor,
			array_map(function(ClientDataPersonaSkinPiece $piece) : PersonaSkinPiece{
				return new PersonaSkinPiece($piece->PieceId, $piece->PieceType, $piece->PackId, $piece->IsDefault, $piece->ProductId);
			}, $clientData->PersonaPieces),
			array_map(function(ClientDataPersonaPieceTintColor $tint) : PersonaPieceTintColor{
				return new PersonaPieceTintColor($tint->PieceType, $tint->Colors);
			}, $clientData->PieceTintColors),
			true,
			$clientData->PremiumSkin,
			$clientData->PersonaSkin,
			$clientData->CapeOnClassicSkin,
			true, //assume this is true? there's no field for it ...
			$clientData->OverrideSkin ?? true,
		);
	}
}
