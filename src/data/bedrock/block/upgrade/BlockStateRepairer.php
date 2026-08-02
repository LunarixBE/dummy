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

namespace pocketmine\data\bedrock\block\upgrade;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateDeserializeException;
use pocketmine\data\bedrock\block\BlockStateDeserializer;

final class BlockStateRepairer{

	/**
	 * @var BlockStateData[]|null[]
	 * @phpstan-var array<string, BlockStateData|null>
	 */
	private array $cache = [];

	public function __construct(
		private readonly BlockStateUpgrader $blockStateUpgrader,
		private readonly BlockStateDeserializer $blockStateDeserializer
	){}

	public function repair(BlockStateData $blockStateData) : ?BlockStateData{
		$cacheKey = $blockStateData->toNbt()->toString();
		if(array_key_exists($cacheKey, $this->cache)){
			return $this->cache[$cacheKey];
		}

		return $this->cache[$cacheKey] = $this->findRepairedState($blockStateData);
	}

	private function findRepairedState(BlockStateData $blockStateData) : ?BlockStateData{
		foreach($this->blockStateUpgrader->getSchemaVersionIds() as $versionId){
			if($versionId > $blockStateData->getVersion()){
				continue;
			}

			$candidate = $this->blockStateUpgrader->upgrade(new BlockStateData(
				$blockStateData->getName(),
				$blockStateData->getStates(),
				$versionId - 1
			));

			try{
				$this->blockStateDeserializer->deserialize($candidate);
			}catch(BlockStateDeserializeException){
				continue;
			}

			return $candidate;
		}

		return null;
	}
}
