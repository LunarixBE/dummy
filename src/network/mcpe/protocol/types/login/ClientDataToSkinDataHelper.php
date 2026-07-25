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
		$skinBytes = self::safeB64Decode($clientData->SkinData ?? "", "SkinData");
		$capeBytes = self::safeB64Decode($clientData->CapeData ?? "", "CapeData");

		$skinResourcePatch = $clientData->SkinResourcePatch ?? "";
		$skinGeometryName = $clientData->SkinGeometryName ?? "";
		$resourcePatch = $skinResourcePatch !== "" ?
			self::safeB64Decode($skinResourcePatch, "SkinResourcePatch") :
			json_encode(["geometry" => ["default" => $skinGeometryName !== "" ? $skinGeometryName : "geometry.humanoid.custom"]], JSON_THROW_ON_ERROR);

		$skinGeometryData = $clientData->SkinGeometryData ?? "";
		$geometryData = $skinGeometryData !== "" ?
			self::safeB64Decode($skinGeometryData, "SkinGeometryData") :
			self::safeB64Decode($clientData->SkinGeometry ?? "", "SkinGeometry");

		$skinImageHeight = $clientData->SkinImageHeight ?? 0;
		$skinImageWidth = $clientData->SkinImageWidth ?? 0;
		$skinImage = $skinImageHeight > 0 && $skinImageWidth > 0 ?
			new SkinImage($skinImageHeight, $skinImageWidth, $skinBytes) :
			SkinImage::fromLegacy($skinBytes);

		$capeImageHeight = $clientData->CapeImageHeight ?? 0;
		$capeImageWidth = $clientData->CapeImageWidth ?? 0;
		$capeImage = $capeBytes !== "" ?
			($capeImageHeight > 0 && $capeImageWidth > 0 ?
				new SkinImage($capeImageHeight, $capeImageWidth, $capeBytes) :
				SkinImage::fromLegacy($capeBytes)) :
			new SkinImage(0, 0, "");
		$skinAnimationData = $clientData->SkinAnimationData ?? "";
		if($skinAnimationData === ""){
			$skinAnimationData = $clientData->AnimationData ?? "";
		}

		/** @var SkinAnimation[] $animations */
		$animations = [];
		foreach($clientData->AnimatedImageData ?? [] as $k => $animation){
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
			$clientData->SkinId ?? "Standard_Custom",
			$clientData->PlayFabId ?? "",
			$resourcePatch,
			$skinImage,
			$animations,
			$capeImage,
			$geometryData,
			self::safeB64Decode($clientData->SkinGeometryDataEngineVersion ?? "", "SkinGeometryDataEngineVersion"), //yes, they actually base64'd the version!
			self::safeB64Decode($skinAnimationData, "SkinAnimationData"),
			$clientData->CapeId ?? "",
			null,
			$clientData->ArmSize ?? SkinData::ARM_SIZE_WIDE,
			$clientData->SkinColor ?? "",
			array_map(function(ClientDataPersonaSkinPiece $piece) : PersonaSkinPiece{
				return new PersonaSkinPiece($piece->PieceId, $piece->PieceType, $piece->PackId, $piece->IsDefault, $piece->ProductId);
			}, $clientData->PersonaPieces ?? []),
			array_map(function(ClientDataPersonaPieceTintColor $tint) : PersonaPieceTintColor{
				return new PersonaPieceTintColor($tint->PieceType, $tint->Colors);
			}, $clientData->PieceTintColors ?? []),
			true,
			$clientData->PremiumSkin ?? false,
			$clientData->PersonaSkin ?? false,
			$clientData->CapeOnClassicSkin ?? false,
			true, //assume this is true? there's no field for it ...
			$clientData->OverrideSkin ?? true,
		);
	}
}
