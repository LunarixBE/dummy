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
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use Ramsey\Uuid\UuidInterface;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

class PlayerSkinPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_SKIN_PACKET;

	public UuidInterface $uuid;
	public string $oldSkinName = "";
	public string $newSkinName = "";
	public SkinData $skin;

	/**
	 * @generate-create-func
	 */
	public static function create(UuidInterface $uuid, string $oldSkinName, string $newSkinName, SkinData $skin) : self{
		$result = new self();
		$result->uuid = $uuid;
		$result->oldSkinName = $oldSkinName;
		$result->newSkinName = $newSkinName;
		$result->skin = $skin;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->uuid = $in->getUUID();
		if($in->getProtocolId() < ProtocolInfo::PROTOCOL_1_16_0){
			$skinId = $in->getString();
			$this->newSkinName = $in->getString();
			$this->oldSkinName = $in->getString();
			$skinData = $in->getString();
			$capeData = $in->getString();
			$geometryName = $in->getString();
			$geometryData = $in->getString();
			$this->skin = new SkinData(
				$skinId,
				"",
				self::resourcePatchFromLegacyGeometryName($geometryName),
				SkinImage::fromLegacy($skinData),
				[],
				$capeData !== "" ? SkinImage::fromLegacy($capeData) : new SkinImage(0, 0, ""),
				$geometryData,
				premium: $in->getBool()
			);
			return;
		}
		$this->skin = $in->getSkin();
		$this->newSkinName = $in->getString();
		$this->oldSkinName = $in->getString();
		$this->skin->setVerified($in->getBool());
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUUID($this->uuid);
		if($out->getProtocolId() < ProtocolInfo::PROTOCOL_1_16_0){
			$out->putString($this->skin->getSkinId());
			$out->putString($this->newSkinName);
			$out->putString($this->oldSkinName);
			$out->putString($this->skin->getSkinImage()->getData());
			$out->putString($this->skin->getCapeImage()->getData());
			$out->putString(self::legacyGeometryNameFromResourcePatch($this->skin->getResourcePatch()));
			$out->putString($this->skin->getGeometryData());
			$out->putBool($this->skin->isPremium());
			return;
		}
		$out->putSkin($this->skin);
		$out->putString($this->newSkinName);
		$out->putString($this->oldSkinName);
		$out->putBool($this->skin->isVerified());
	}

	private static function legacyGeometryNameFromResourcePatch(string $resourcePatch) : string{
		$decoded = json_decode($resourcePatch, true);
		if(is_array($decoded) && isset($decoded["geometry"]["default"]) && is_string($decoded["geometry"]["default"])){
			return $decoded["geometry"]["default"];
		}
		return "geometry.humanoid.custom";
	}

	private static function resourcePatchFromLegacyGeometryName(string $geometryName) : string{
		return (string) json_encode([
			"geometry" => [
				"default" => $geometryName !== "" ? $geometryName : "geometry.humanoid.custom"
			]
		]);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerSkin($this);
	}
}
