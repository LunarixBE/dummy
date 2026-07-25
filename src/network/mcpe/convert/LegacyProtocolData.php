<?php

/*
 *
 *  _____                    _   _       _
 * | ____|___ ___  ___ _ __ | |_(_) __ _| |
 * |  _| / __/ __|/ _ \ '_ \| __| |/ _` | |
 * | |___\__ \__ \  __/ | | | |_| | (_| | |
 * |_____|___/___/\___|_| |_|\__|_|\__,_|_|
 *
 * Essential — PocketMine-MP Fork
 * Supported MCPE/Bedrock versions: 1.12, 1.16 - 1.26.x
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Essential Team
 * @link https://github.com/BakuTeam/Essential
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\convert;

use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use function count;
use function get_debug_type;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function strlen;

/**
 * Exact wire data needed by Bedrock 1.12.x (protocol 361).
 *
 * @internal
 */
final class LegacyProtocolData{
	private const FALLBACK_BLOCK_NAME = "minecraft:dirt";
	private const FALLBACK_BLOCK_META = 0;

	/**
	 * @var array<int, array{name: string, data: int, legacy_id: int}>|null
	 * @phpstan-var list<array{name: string, data: int, legacy_id: int}>|null
	 */
	private static ?array $r12BlockTable = null;
	private static ?string $r12BlockTableCache = null;
	private static ?string $r12ItemTableCache = null;

	/**
	 * @var int[]|null
	 * @phpstan-var array<string, int>|null
	 */
	private static ?array $r12StringToLegacyBlockId = null;

	/**
	 * @var int[]|null
	 * @phpstan-var array<int, int>|null legacy full ID => runtime ID
	 */
	private static ?array $r12LegacyFullIdToRuntimeId = null;

	private static ?int $fallbackLegacyFullId = null;
	private static ?int $fallbackRuntimeId = null;

	private function __construct(){
		//NOOP
	}

	private static function putString(BinaryStream $stream, string $value) : void{
		$stream->putUnsignedVarInt(strlen($value));
		$stream->put($value);
	}

	/**
	 * @return mixed[]
	 */
	private static function loadJsonMap(string $file) : array{
		$result = json_decode(Filesystem::fileGetContents($file), true);
		if(!is_array($result)){
			throw new AssumptionFailedError("Invalid legacy protocol data in $file, expected array, got " . get_debug_type($result));
		}
		return $result;
	}

	/**
	 * @return int[]
	 * @phpstan-return array<string, int>
	 */
	private static function getR12StringToLegacyBlockId() : array{
		if(self::$r12StringToLegacyBlockId === null){
			$result = [];
			foreach(Utils::promoteKeys(self::loadJsonMap(BedrockDataFiles::LEGACY_BLOCK_ID_MAP_1_12_JSON)) as $name => $legacyId){
				if(!is_string($name) || !is_int($legacyId)){
					throw new AssumptionFailedError("Invalid 1.12 block ID map entry");
				}
				$result[$name] = $legacyId;
			}
			self::$r12StringToLegacyBlockId = $result;
		}
		return self::$r12StringToLegacyBlockId;
	}

	/**
	 * @return array<int, array{name: string, data: int, legacy_id: int}>
	 * @phpstan-return list<array{name: string, data: int, legacy_id: int}>
	 */
	private static function getR12BlockTable() : array{
		if(self::$r12BlockTable === null){
			$legacyIdMap = self::getR12StringToLegacyBlockId();
			$compressedTable = self::loadJsonMap(BedrockDataFiles::LEGACY_REQUIRED_BLOCK_STATES_1_12_JSON);

			$table = [];
			foreach(Utils::promoteKeys($compressedTable) as $prefix => $entries){
				if(!is_string($prefix) || !is_array($entries)){
					throw new AssumptionFailedError("Invalid 1.12 required block states namespace");
				}
				foreach(Utils::promoteKeys($entries) as $shortStringId => $states){
					if(!is_string($shortStringId) || !is_array($states)){
						throw new AssumptionFailedError("Invalid 1.12 required block states entry");
					}
					$name = "$prefix:$shortStringId";
					if(!isset($legacyIdMap[$name])){
						throw new AssumptionFailedError("Missing 1.12 legacy block ID for $name");
					}
					foreach($states as $state){
						if(!is_int($state)){
							throw new AssumptionFailedError("Invalid 1.12 required block state meta for $name");
						}
						$table[] = [
							"name" => $name,
							"data" => $state,
							"legacy_id" => $legacyIdMap[$name]
						];
					}
				}
			}

			self::$r12BlockTable = $table;
		}
		return self::$r12BlockTable;
	}

	public static function getR12EncodedBlockTable() : string{
		if(self::$r12BlockTableCache === null){
			$stream = new BinaryStream();
			$table = self::getR12BlockTable();
			$stream->putUnsignedVarInt(count($table));
			foreach($table as $entry){
				self::putString($stream, $entry["name"]);
				$stream->putLShort($entry["data"]);
				$stream->putLShort($entry["legacy_id"]);
			}
			self::$r12BlockTableCache = $stream->getBuffer();
		}
		return self::$r12BlockTableCache;
	}

	public static function getR12EncodedItemTable() : string{
		if(self::$r12ItemTableCache === null){
			$table = self::loadJsonMap(BedrockDataFiles::LEGACY_ITEM_ID_MAP_1_12_JSON);

			$stream = new BinaryStream();
			$stream->putUnsignedVarInt(count($table));
			foreach(Utils::promoteKeys($table) as $name => $legacyId){
				if(!is_string($name) || !is_int($legacyId)){
					throw new AssumptionFailedError("Invalid 1.12 item ID map entry");
				}
				self::putString($stream, $name);
				$stream->putLShort($legacyId);
			}
			self::$r12ItemTableCache = $stream->getBuffer();
		}
		return self::$r12ItemTableCache;
	}

	public static function legacyFullIdFromNameMeta(string $name, int $meta) : int{
		$legacyId = self::getR12StringToLegacyBlockId()[$name] ?? self::getR12StringToLegacyBlockId()[self::FALLBACK_BLOCK_NAME] ?? null;
		if($legacyId === null){
			throw new AssumptionFailedError("Missing fallback 1.12 block ID");
		}
		if($meta < 0 || $meta > 15){
			$meta = self::FALLBACK_BLOCK_META;
		}
		return ($legacyId << 4) | $meta;
	}

	/**
	 * @return int[]
	 * @phpstan-return array<int, int>
	 */
	private static function getR12LegacyFullIdToRuntimeId() : array{
		if(self::$r12LegacyFullIdToRuntimeId === null){
			$lookup = [];
			foreach(self::getR12BlockTable() as $runtimeId => $entry){
				if($entry["data"] > 15){
					continue;
				}
				$lookup[($entry["legacy_id"] << 4) | $entry["data"]] = $runtimeId;
			}
			self::$r12LegacyFullIdToRuntimeId = $lookup;
		}
		return self::$r12LegacyFullIdToRuntimeId;
	}

	private static function getFallbackLegacyFullId() : int{
		return self::$fallbackLegacyFullId ??= self::legacyFullIdFromNameMeta(self::FALLBACK_BLOCK_NAME, self::FALLBACK_BLOCK_META);
	}

	public static function runtimeIdFromLegacyFullId(int $legacyFullId) : int{
		$lookup = self::getR12LegacyFullIdToRuntimeId();
		if(isset($lookup[$legacyFullId])){
			return $lookup[$legacyFullId];
		}

		return self::$fallbackRuntimeId ??= $lookup[self::getFallbackLegacyFullId()] ?? throw new AssumptionFailedError("Missing fallback 1.12 runtime ID");
	}
}
