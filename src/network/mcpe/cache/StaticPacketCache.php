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

namespace pocketmine\network\mcpe\cache;

use pocketmine\color\Color;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\types\biome\BiomeDefinitionEntry;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;
use pocketmine\world\biome\model\BiomeDefinitionEntryData;
use function count;
use function get_debug_type;
use function is_array;
use function json_decode;
use function zlib_decode;

class StaticPacketCache{
	use SingletonTrait;

	/**
	 * @phpstan-return CacheableNbt<CompoundTag>
	 */
	protected static function loadCompoundFromFile(string $filePath) : CacheableNbt{
		return new CacheableNbt((new NetworkNbtSerializer())->read(Filesystem::fileGetContents($filePath))->mustGetCompoundTag());
	}

	/**
	 * @phpstan-return CacheableNbt<CompoundTag>
	 */
	private static function loadGzippedCompoundFromFile(string $filePath) : CacheableNbt{
		return new CacheableNbt(self::readGzippedCompound($filePath));
	}

	private static function readGzippedCompound(string $filePath) : CompoundTag{
		$raw = zlib_decode(Filesystem::fileGetContents($filePath));
		if($raw === false){
			throw new SavedDataLoadingException("Failed to decompress $filePath");
		}
		return (new BigEndianNbtSerializer())->read($raw)->mustGetCompoundTag();
	}

	/**
	 * Loads the 1.26.40+ biome_definitions.nbt, which uses the same string-pooled layout as BiomeDefinitionListPacket.
	 * It has no spore/ash densities - those were replaced by foliageSnow.
	 *
	 * @return list<BiomeDefinitionEntry>
	 */
	private static function loadBiomeDefinitionsFromNbt(string $filePath) : array{
		$root = self::readGzippedCompound($filePath);

		$stringListTag = $root->getListTag("biomeStringList") ?? throw new SavedDataLoadingException("$filePath missing biomeStringList");
		$strings = [];
		foreach($stringListTag as $i => $tag){
			if(!($tag instanceof StringTag)){
				throw new SavedDataLoadingException("biomeStringList should only contain strings");
			}
			$strings[$i] = $tag->getValue();
		}
		$locateString = fn(int $index) : string => $strings[$index] ?? throw new SavedDataLoadingException("$filePath refers to unknown string index $index");

		$biomeDataTag = $root->getListTag("biomeData") ?? throw new SavedDataLoadingException("$filePath missing biomeData");
		$entries = [];
		foreach($biomeDataTag as $entryTag){
			if(!($entryTag instanceof CompoundTag)){
				throw new SavedDataLoadingException("biomeData should only contain compounds");
			}
			$data = $entryTag->getCompoundTag("data") ?? throw new SavedDataLoadingException("Biome entry is missing data");

			$tags = null;
			$tagsTag = $data->getCompoundTag("tags")?->getListTag("tags");
			if($tagsTag !== null){
				$tags = [];
				foreach($tagsTag as $tagIndexTag){
					if(!($tagIndexTag instanceof ShortTag)){
						throw new SavedDataLoadingException("Biome tag list should only contain shorts");
					}
					$tags[] = $locateString($tagIndexTag->getValue() & 0xffff);
				}
			}

			$entries[] = new BiomeDefinitionEntry(
				$locateString($entryTag->getShort("index") & 0xffff),
				$data->getShort("id") & 0xffff,
				$data->getFloat("temperature"),
				$data->getFloat("downfall"),
				0.0, //redSporeDensity
				0.0, //blueSporeDensity
				0.0, //ashDensity
				0.0, //whiteAshDensity
				$data->getFloat("depth"),
				$data->getFloat("scale"),
				Color::fromARGB($data->getInt("mapWaterColorARGB") & 0xffffffff),
				$data->getByte("rain") !== 0,
				$tags,
				null,
				$data->getFloat("foliageSnow"),
			);
		}

		return $entries;
	}

	/**
	 * @return list<BiomeDefinitionEntry>
	 */
	private static function loadBiomeDefinitionModel(string $filePath) : array{
		$biomeEntries = json_decode(Filesystem::fileGetContents($filePath), associative: true);
		if(!is_array($biomeEntries)){
			throw new SavedDataLoadingException("$filePath root should be an array, got " . get_debug_type($biomeEntries));
		}

		$jsonMapper = new \JsonMapper();
		$jsonMapper->bExceptionOnMissingData = true;
		$jsonMapper->bStrictObjectTypeChecking = true;
		$jsonMapper->bEnforceMapType = false;

		$entries = [];
		foreach(Utils::promoteKeys($biomeEntries) as $biomeName => $entry){
			if(!is_array($entry)){
				throw new SavedDataLoadingException("$filePath should be an array of objects, got " . get_debug_type($entry));
			}

			try{
				$biomeDefinition = $jsonMapper->map($entry, new BiomeDefinitionEntryData());

				$mapWaterColour = $biomeDefinition->mapWaterColour;
				$entries[] = new BiomeDefinitionEntry(
					(string) $biomeName,
					$biomeDefinition->id,
					$biomeDefinition->temperature,
					$biomeDefinition->downfall,
					$biomeDefinition->redSporeDensity,
					$biomeDefinition->blueSporeDensity,
					$biomeDefinition->ashDensity,
					$biomeDefinition->whiteAshDensity,
					$biomeDefinition->depth,
					$biomeDefinition->scale,
					new Color(
						$mapWaterColour->r,
						$mapWaterColour->g,
						$mapWaterColour->b,
						$mapWaterColour->a
					),
					$biomeDefinition->rain,
					count($biomeDefinition->tags) > 0 ? $biomeDefinition->tags : null,
				);
			}catch(\JsonMapper_Exception $e){
				throw new \RuntimeException($e->getMessage(), 0, $e);
			}
		}

		return $entries;
	}

	private static function make() : self{
		return new self(
			BiomeDefinitionListPacket::fromDefinitions(self::loadBiomeDefinitionsFromNbt(BedrockDataFiles::BIOME_DEFINITIONS_1_26_40_NBT)),
			BiomeDefinitionListPacket::fromDefinitions(self::loadBiomeDefinitionModel(BedrockDataFiles::BIOME_DEFINITIONS_JSON)),
			BiomeDefinitionListPacket::createLegacy(self::loadCompoundFromFile(BedrockDataFiles::BIOME_DEFINITIONS_NBT)),
			AvailableActorIdentifiersPacket::create(self::loadGzippedCompoundFromFile(BedrockDataFiles::ENTITY_IDENTIFIERS_1_26_40_NBT)),
			AvailableActorIdentifiersPacket::create(self::loadCompoundFromFile(BedrockDataFiles::ENTITY_IDENTIFIERS_NBT)),
			AvailableActorIdentifiersPacket::create(self::loadCompoundFromFile(BedrockDataFiles::ENTITY_IDENTIFIERS_1_16_100_NBT))
		);
	}

	public function __construct(
		private BiomeDefinitionListPacket $biomeDefs1_26_40,
		private BiomeDefinitionListPacket $biomeDefs,
		private BiomeDefinitionListPacket $legacyBiomeDefs,
		private AvailableActorIdentifiersPacket $availableActorIdentifiers1_26_40,
		private AvailableActorIdentifiersPacket $availableActorIdentifiers,
		private AvailableActorIdentifiersPacket $legacyAvailableActorIdentifiers
	){}

	public function getBiomeDefs(int $protocolId) : BiomeDefinitionListPacket{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			return $this->biomeDefs1_26_40;
		}
		return $protocolId >= ProtocolInfo::PROTOCOL_1_21_80 ? $this->biomeDefs : $this->legacyBiomeDefs;
	}

	public function getAvailableActorIdentifiers(int $protocolId) : AvailableActorIdentifiersPacket{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			return $this->availableActorIdentifiers1_26_40;
		}
		return $protocolId <= ProtocolInfo::PROTOCOL_1_16_210 ? $this->legacyAvailableActorIdentifiers : $this->availableActorIdentifiers;
	}
}
