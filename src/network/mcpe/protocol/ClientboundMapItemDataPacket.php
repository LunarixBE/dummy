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

use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\MapDecoration;
use pocketmine\network\mcpe\protocol\types\MapImage;
use pocketmine\network\mcpe\protocol\types\MapTrackedObject;
use pocketmine\utils\Binary;
use function array_chunk;
use function count;

class ClientboundMapItemDataPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET;

	public const BITFLAG_TEXTURE_UPDATE = 0x02;
	public const BITFLAG_DECORATION_UPDATE = 0x04;
	public const BITFLAG_MAP_CREATION = 0x08;

	public int $mapId;
	public int $type;
	public int $dimensionId = DimensionIds::OVERWORLD;
	public bool $isLocked = false;
	public BlockPosition $origin;

	/** @var int[] */
	public array $parentMapIds = [];
	public int $scale = 0;

	/** @var MapTrackedObject[] */
	public array $trackedEntities = [];
	/** @var MapDecoration[] */
	public array $decorations = [];

	public int $xOffset = 0;
	public int $yOffset = 0;
	public ?MapImage $colors = null;

	protected function decodePayload(PacketSerializer $in) : void{
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->decodePayload1_26_40($in);
			return;
		}

		$this->mapId = $in->getActorUniqueId();
		$this->type = $in->getUnsignedVarInt();
		$this->dimensionId = $in->getByte();
		$this->isLocked = $in->getBool();
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_19_20){
			$this->origin = $in->getSignedBlockPosition();
		}

		if(($this->type & self::BITFLAG_MAP_CREATION) !== 0){
			$count = $in->getUnsignedVarInt();
			for($i = 0; $i < $count; ++$i){
				$this->parentMapIds[] = $in->getActorUniqueId();
			}
		}

		if(($this->type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_DECORATION_UPDATE | self::BITFLAG_TEXTURE_UPDATE)) !== 0){ //Decoration bitflag or colour bitflag
			$this->scale = $in->getByte();
		}

		if(($this->type & self::BITFLAG_DECORATION_UPDATE) !== 0){
			for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
				$object = new MapTrackedObject();
				$object->type = $in->getLInt();
				if($object->type === MapTrackedObject::TYPE_BLOCK){
					$object->blockPosition = $in->getBlockPosition($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_10);
				}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
					$object->actorUniqueId = $in->getActorUniqueId();
				}else{
					throw new PacketDecodeException("Unknown map object type $object->type");
				}
				$this->trackedEntities[] = $object;
			}

			for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
				$icon = $in->getByte();
				$rotation = $in->getByte();
				$xOffset = $in->getByte();
				$yOffset = $in->getByte();
				$label = $in->getString();
				$color = Color::fromRGBA(Binary::flipIntEndianness($in->getUnsignedVarInt()));
				$this->decorations[] = new MapDecoration($icon, $rotation, $xOffset, $yOffset, $label, $color);
			}
		}

		if(($this->type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
			$width = $in->getVarInt();
			$height = $in->getVarInt();
			$this->xOffset = $in->getVarInt();
			$this->yOffset = $in->getVarInt();

			$count = $in->getUnsignedVarInt();
			if($count !== $width * $height){
				throw new PacketDecodeException("Expected colour count of " . ($height * $width) . " (height $height * width $width), got $count");
			}

			$this->colors = MapImage::decode($in, $height, $width);
		}
	}

	/**
	 * >= PROTOCOL_1_26_40 dropped the bitflags - every section is an independent optional. The flags are rebuilt from
	 * which sections are present.
	 */
	private function decodePayload1_26_40(PacketSerializer $in) : void{
		$this->mapId = $in->getActorUniqueId();
		$this->dimensionId = $in->getByte();
		$this->isLocked = $in->getBool();
		$this->origin = $in->getSignedBlockPosition();
		$this->type = 0;

		$parentMapIds = $in->readOptional(function() use ($in) : array{
			$ids = [];
			for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
				$ids[] = $in->getActorUniqueId();
			}
			return $ids;
		});
		if($parentMapIds !== null){
			$this->parentMapIds = $parentMapIds;
			$this->type |= self::BITFLAG_MAP_CREATION;
		}

		$this->scale = $in->readOptional(fn() => $in->getByte()) ?? 0;

		$this->trackedEntities = $in->readOptional(function() use ($in) : array{
			$objects = [];
			for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
				$objects[] = MapTrackedObject::read($in);
			}
			return $objects;
		}) ?? [];

		$decorations = $in->readOptional(function() use ($in) : array{
			$decorations = [];
			for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
				$icon = $in->getByte();
				$rotation = $in->getByte();
				$xOffset = $in->getByte();
				$yOffset = $in->getByte();
				$label = $in->getString();
				$color = Color::fromRGBA(Binary::flipIntEndianness($in->getLInt()));
				$decorations[] = new MapDecoration($icon, $rotation, $xOffset, $yOffset, $label, $color);
			}
			return $decorations;
		});
		if($decorations !== null){
			$this->decorations = $decorations;
			$this->type |= self::BITFLAG_DECORATION_UPDATE;
		}

		$width = $in->readOptional(fn() => $in->getVarInt());
		$height = $in->readOptional(fn() => $in->getVarInt());
		$this->xOffset = $in->readOptional(fn() => $in->getVarInt()) ?? 0;
		$this->yOffset = $in->readOptional(fn() => $in->getVarInt()) ?? 0;

		$pixels = $in->readOptional(function() use ($in) : array{
			$pixels = [];
			for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
				$pixels[] = Color::fromRGBA(Binary::flipIntEndianness($in->getLInt()));
			}
			return $pixels;
		});
		if($pixels !== null && $width !== null && $height !== null && $width > 0 && count($pixels) === $width * $height){
			$this->colors = new MapImage(array_chunk($pixels, $width));
			$this->type |= self::BITFLAG_TEXTURE_UPDATE;
		}
	}

	private function encodePayload1_26_40(PacketSerializer $out) : void{
		$out->putActorUniqueId($this->mapId);
		$out->putByte($this->dimensionId);
		$out->putBool($this->isLocked);
		$out->putSignedBlockPosition($this->origin);

		$out->writeOptional(count($this->parentMapIds) > 0 ? $this->parentMapIds : null, function(array $parentMapIds) use ($out) : void{
			$out->putUnsignedVarInt(count($parentMapIds));
			foreach($parentMapIds as $parentMapId){
				$out->putActorUniqueId($parentMapId);
			}
		});

		$out->writeOptional($this->scale, fn(int $scale) => $out->putByte($scale));

		$out->writeOptional(count($this->trackedEntities) > 0 ? $this->trackedEntities : null, function(array $trackedEntities) use ($out) : void{
			$out->putUnsignedVarInt(count($trackedEntities));
			foreach($trackedEntities as $object){
				$object->write($out);
			}
		});

		$out->writeOptional(count($this->decorations) > 0 ? $this->decorations : null, function(array $decorations) use ($out) : void{
			$out->putUnsignedVarInt(count($decorations));
			foreach($decorations as $decoration){
				$out->putByte($decoration->getIcon());
				$out->putByte($decoration->getRotation());
				$out->putByte($decoration->getXOffset());
				$out->putByte($decoration->getYOffset());
				$out->putString($decoration->getLabel());
				$out->putLInt(Binary::flipIntEndianness($decoration->getColor()->toRGBA()));
			}
		});

		$colors = $this->colors;
		$out->writeOptional($colors?->getWidth(), fn(int $v) => $out->putVarInt($v));
		$out->writeOptional($colors?->getHeight(), fn(int $v) => $out->putVarInt($v));
		$out->writeOptional($colors !== null ? $this->xOffset : null, fn(int $v) => $out->putVarInt($v));
		$out->writeOptional($colors !== null ? $this->yOffset : null, fn(int $v) => $out->putVarInt($v));

		$out->writeOptional($colors, function(MapImage $colors) use ($out) : void{
			$out->putUnsignedVarInt($colors->getWidth() * $colors->getHeight());
			foreach($colors->getPixels() as $row){
				foreach($row as $pixel){
					$out->putLInt(Binary::flipIntEndianness($pixel->toRGBA()));
				}
			}
		});
	}

	protected function encodePayload(PacketSerializer $out) : void{
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->encodePayload1_26_40($out);
			return;
		}

		$out->putActorUniqueId($this->mapId);

		$type = 0;
		if(($parentMapIdsCount = count($this->parentMapIds)) > 0){
			$type |= self::BITFLAG_MAP_CREATION;
		}
		if(($decorationCount = count($this->decorations)) > 0){
			$type |= self::BITFLAG_DECORATION_UPDATE;
		}
		if($this->colors !== null){
			$type |= self::BITFLAG_TEXTURE_UPDATE;
		}

		$out->putUnsignedVarInt($type);
		$out->putByte($this->dimensionId);
		$out->putBool($this->isLocked);
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_19_20){
			$out->putSignedBlockPosition($this->origin);
		}

		if(($type & self::BITFLAG_MAP_CREATION) !== 0){
			$out->putUnsignedVarInt($parentMapIdsCount);
			foreach($this->parentMapIds as $parentMapId){
				$out->putActorUniqueId($parentMapId);
			}
		}

		if(($type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_TEXTURE_UPDATE | self::BITFLAG_DECORATION_UPDATE)) !== 0){
			$out->putByte($this->scale);
		}

		if(($type & self::BITFLAG_DECORATION_UPDATE) !== 0){
			$out->putUnsignedVarInt(count($this->trackedEntities));
			foreach($this->trackedEntities as $object){
				$out->putLInt($object->type);
				if($object->type === MapTrackedObject::TYPE_BLOCK){
					$out->putBlockPosition($object->blockPosition, $out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_10);
				}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
					$out->putActorUniqueId($object->actorUniqueId);
				}else{
					throw new \InvalidArgumentException("Unknown map object type $object->type");
				}
			}

			$out->putUnsignedVarInt($decorationCount);
			foreach($this->decorations as $decoration){
				$out->putByte($decoration->getIcon());
				$out->putByte($decoration->getRotation());
				$out->putByte($decoration->getXOffset());
				$out->putByte($decoration->getYOffset());
				$out->putString($decoration->getLabel());
				$out->putUnsignedVarInt(Binary::flipIntEndianness($decoration->getColor()->toRGBA()));
			}
		}

		if($this->colors !== null){
			$out->putVarInt($this->colors->getWidth());
			$out->putVarInt($this->colors->getHeight());
			$out->putVarInt($this->xOffset);
			$out->putVarInt($this->yOffset);

			$out->putUnsignedVarInt($this->colors->getWidth() * $this->colors->getHeight()); //list count, but we handle it as a 2D array... thanks for the confusion mojang

			$this->colors->encode($out);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundMapItemData($this);
	}
}
