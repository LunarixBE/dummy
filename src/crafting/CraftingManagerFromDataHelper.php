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

namespace pocketmine\crafting;

use pocketmine\crafting\json\ItemStackData;
use pocketmine\crafting\json\PotionContainerChangeRecipeData;
use pocketmine\crafting\json\PotionTypeRecipeData;
use pocketmine\crafting\json\RecipeIngredientData;
use pocketmine\crafting\json\ShapedRecipeData;
use pocketmine\crafting\json\ShapelessRecipeData;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\errorhandler\ErrorToExceptionHandler;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use Symfony\Component\Filesystem\Path;
use function base64_decode;
use function count;
use function get_debug_type;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function json_decode;

final class CraftingManagerFromDataHelper{

	/** recipe types as they appear in the 1.26.40+ recipes.json */
	private const NETWORK_RECIPE_TYPE_SHAPED = 0;
	private const NETWORK_RECIPE_TYPE_SHAPELESS = 1;
	private const NETWORK_RECIPE_TYPE_MULTI = 2;
	private const NETWORK_RECIPE_TYPE_USER_DATA_SHAPELESS = 3;
	private const NETWORK_RECIPE_TYPE_SMITHING_TRANSFORM = 6;
	private const NETWORK_RECIPE_TYPE_SMITHING_TRIM = 7;

	private const NETWORK_INGREDIENT_WILDCARD_META = 0x7fff;

	private const LEGACY_ALIAS_MAX_META = 15;

	private static function deserializeIngredient(RecipeIngredientData $data) : ?RecipeIngredient{
		if(isset($data->count) && $data->count !== 1){
			//every case we've seen so far where this isn't the case, it's been a bug and the count was ignored anyway
			//e.g. gold blocks crafted from 9 ingots, but each input item individually had a count of 9
			throw new SavedDataLoadingException("Recipe inputs should have a count of exactly 1");
		}
		if(isset($data->tag)){
			return new TagWildcardRecipeIngredient($data->tag);
		}

		$meta = $data->meta ?? null;
		if($meta === RecipeIngredientData::WILDCARD_META_VALUE){
			//this could be an unimplemented item, but it doesn't really matter, since the item shouldn't be able to
			//be obtained anyway - filtering unknown items is only really important for outputs, to prevent players
			//obtaining them
			return new MetaWildcardRecipeIngredient($data->name);
		}

		$itemStack = self::deserializeItemStackFromFields(
			$data->name,
			$meta,
			$data->count ?? null,
			self::decodeNbtField($data->block_states ?? null),
			null,
			[],
			[]
		);
		if($itemStack === null){
			//probably unknown item
			return null;
		}
		return new ExactRecipeIngredient($itemStack);
	}

	public static function deserializeItemStack(ItemStackData $data) : ?Item{
		//count, name, block_name, block_states, meta, nbt, can_place_on, can_destroy
		return self::deserializeItemStackFromFields(
			$data->name,
			$data->meta ?? null,
			$data->count ?? null,
			self::decodeNbtField($data->block_states ?? null),
			self::decodeNbtField($data->nbt ?? null),
			$data->can_place_on ?? [],
			$data->can_destroy ?? []
		);
	}

	private static function decodeNbtField(?string $raw) : ?CompoundTag{
		return $raw === null ? null : (new LittleEndianNbtSerializer())
			->read(ErrorToExceptionHandler::trapAndRemoveFalse(fn() => base64_decode($raw, true)))
			->mustGetCompoundTag();
	}

	/**
	 * Deserializes an item stack described by string ID + meta, the way network recipe and creative data does.
	 * Blockitems without explicit blockstates get theirs looked up from the block palette by meta.
	 *
	 * @param string[] $canPlaceOn
	 * @param string[] $canDestroy
	 */
	public static function deserializeItemStackFromFields(string $name, ?int $meta, ?int $count, ?CompoundTag $blockStatesTag, ?CompoundTag $nbt, array $canPlaceOn = [], array $canDestroy = []) : ?Item{
		$count ??= 1;

		$blockName = BlockItemIdMap::getInstance()->lookupBlockId($name);
		if($blockName !== null){
			if($blockStatesTag !== null){
				$blockStateData = BlockStateData::current($blockName, $blockStatesTag->getValue());
			}else{
				//look up the state from the network block palette
				$dictionary = TypeConverter::getInstance()->getBlockTranslator()->getBlockStateDictionary();
				$stateId = $dictionary->lookupStateIdFromIdMeta($blockName, $meta ?? 0) ?? $dictionary->lookupStateIdFromIdMeta($blockName, 0);
				if($stateId === null){
					//unknown block
					return null;
				}
				$blockStateData = $dictionary->generateDataFromStateId($stateId);
			}
			$meta = 0;
		}else{
			$blockStateData = null;
		}

		$itemStackData = new SavedItemStackData(
			new SavedItemData(
				$name,
				$meta ?? 0,
				$blockStateData,
				$nbt
			),
			$count,
			null,
			null,
			$canPlaceOn,
			$canDestroy,
		);

		try{
			return GlobalItemDataHandlers::getDeserializer()->deserializeStack($itemStackData);
		}catch(ItemTypeDeserializeException){
			//probably unknown item
			return null;
		}
	}

	/**
	 * @return mixed[]
	 *
	 * @phpstan-template TData of object
	 * @phpstan-param class-string<TData> $modelCLass
	 * @phpstan-return list<TData>
	 */
	public static function loadJsonArrayOfObjectsFile(string $filePath, string $modelCLass) : array{
		$recipes = json_decode(Filesystem::fileGetContents($filePath));
		if(!is_array($recipes)){
			throw new SavedDataLoadingException("$filePath root should be an array, got " . get_debug_type($recipes));
		}

		$mapper = new \JsonMapper();
		$mapper->bStrictObjectTypeChecking = false; //to allow hydrating ItemStackData from compact string entries
		$mapper->bExceptionOnUndefinedProperty = true;
		$mapper->bExceptionOnMissingData = true;

		return self::loadJsonObjectListIntoModel($mapper, $modelCLass, $recipes);
	}

	/**
	 * @phpstan-template TRecipeData of object
	 * @phpstan-param class-string<TRecipeData> $modelClass
	 * @phpstan-return TRecipeData
	 */
	private static function loadJsonObjectIntoModel(\JsonMapper $mapper, string $modelClass, object $data) : object{
		//JsonMapper does this for subtypes, but not for the base type :(
		try{
			return $mapper->map($data, (new \ReflectionClass($modelClass))->newInstanceWithoutConstructor());
		}catch(\JsonMapper_Exception $e){
			throw new SavedDataLoadingException($e->getMessage(), 0, $e);
		}
	}

	/**
	 * @param mixed[] $data
	 * @return object[]
	 *
	 * @phpstan-template TRecipeData of object
	 * @phpstan-param class-string<TRecipeData> $modelClass
	 * @phpstan-return list<TRecipeData>
	 */
	public static function loadJsonObjectListIntoModel(\JsonMapper $mapper, string $modelClass, array $data) : array{
		$result = [];
		foreach(Utils::promoteKeys($data) as $i => $item){
			if(!is_object($item)){
				throw new SavedDataLoadingException("Invalid entry at index $i: expected object, got " . get_debug_type($item));
			}
			try{
				$result[] = self::loadJsonObjectIntoModel($mapper, $modelClass, $item);
			}catch(SavedDataLoadingException $e){
				throw new SavedDataLoadingException("Invalid entry at index $i: " . $e->getMessage(), 0, $e);
			}
		}
		return $result;
	}

	public static function make(string $directoryPath) : CraftingManager{
		$result = new CraftingManager();

		foreach(self::loadJsonArrayOfObjectsFile(Path::join($directoryPath, 'shapeless_crafting.json'), ShapelessRecipeData::class) as $recipe){
			$recipeType = match($recipe->block){
				"crafting_table" => ShapelessRecipeType::CRAFTING,
				"stonecutter" => ShapelessRecipeType::STONECUTTER,
				"smithing_table" => ShapelessRecipeType::SMITHING,
				"cartography_table" => ShapelessRecipeType::CARTOGRAPHY,
				"furnace" => FurnaceType::FURNACE,
				"blast_furnace" => FurnaceType::BLAST_FURNACE,
				"smoker" => FurnaceType::SMOKER,
				"campfire" => FurnaceType::CAMPFIRE,
				"soul_campfire" => FurnaceType::SOUL_CAMPFIRE,
				default => null
			};
			if($recipeType === null){
				continue;
			}
			$inputs = [];
			foreach($recipe->input as $inputData){
				$input = self::deserializeIngredient($inputData);
				if($input === null){ //unknown input item
					continue 2;
				}
				$inputs[] = $input;
			}
			$outputs = [];
			foreach($recipe->output as $outputData){
				$output = self::deserializeItemStack($outputData);
				if($output === null){ //unknown output item
					continue 2;
				}
				$outputs[] = $output;
			}
			//TODO: check unlocking requirements - our current system doesn't support this

			if($recipeType instanceof FurnaceType){
				if(count($inputs) !== 1 || count($outputs) !== 1){
					throw new SavedDataLoadingException("Furnace recipes must have exactly 1 input and 1 output");
				}

				$result->getFurnaceRecipeManager($recipeType)->register(new FurnaceRecipe(
					$outputs[0],
					$inputs[0]
				));
			}else{
				$result->registerShapelessRecipe(new ShapelessRecipe(
					$inputs,
					$outputs,
					$recipeType
				));
			}
		}
		foreach(self::loadJsonArrayOfObjectsFile(Path::join($directoryPath, 'shaped_crafting.json'), ShapedRecipeData::class) as $recipe){
			if($recipe->block !== "crafting_table"){ //TODO: filter others out for now to avoid breaking economics
				continue;
			}
			$inputs = [];
			foreach(Utils::stringifyKeys($recipe->input) as $symbol => $inputData){
				$input = self::deserializeIngredient($inputData);
				if($input === null){ //unknown input item
					continue 2;
				}
				$inputs[$symbol] = $input;
			}
			$outputs = [];
			foreach($recipe->output as $outputData){
				$output = self::deserializeItemStack($outputData);
				if($output === null){ //unknown output item
					continue 2;
				}
				$outputs[] = $output;
			}
			//TODO: check unlocking requirements - our current system doesn't support this
			$result->registerShapedRecipe(new ShapedRecipe(
				$recipe->shape,
				$inputs,
				$outputs
			));
		}
		foreach(self::loadJsonArrayOfObjectsFile(Path::join($directoryPath, 'potion_type.json'), PotionTypeRecipeData::class) as $recipe){
			$input = self::deserializeIngredient($recipe->input);
			$ingredient = self::deserializeIngredient($recipe->ingredient);
			$output = self::deserializeItemStack($recipe->output);
			if($input === null || $ingredient === null || $output === null){
				continue;
			}
			$result->registerPotionTypeRecipe(new PotionTypeRecipe(
				$input,
				$ingredient,
				$output
			));
		}
		foreach(self::loadJsonArrayOfObjectsFile(Path::join($directoryPath, 'potion_container_change.json'), PotionContainerChangeRecipeData::class) as $recipe){
			$ingredient = self::deserializeIngredient($recipe->ingredient);
			if($ingredient === null){
				continue;
			}

			$inputId = $recipe->input_item_name;
			$outputId = $recipe->output_item_name;

			//TODO: this is a really awful way to just check if an ID is recognized ...
			if(
				self::deserializeItemStackFromFields($inputId, null, null, null, null, [], []) === null ||
				self::deserializeItemStackFromFields($outputId, null, null, null, null, [], []) === null
			){
				//unknown item
				continue;
			}
			$result->registerPotionContainerChangeRecipe(new PotionContainerChangeRecipe(
				$inputId,
				$ingredient,
				$outputId
			));
		}

		//TODO: smithing

		return $result;
	}

	private static function deserializeComplexAliasIngredient(string $aliasName) : ?RecipeIngredient{
		$idMetaUpgrader = GlobalItemDataHandlers::getUpgrader()->getIdMetaUpgrader();

		$items = [];
		$seenIds = [];
		for($meta = 0; $meta <= self::LEGACY_ALIAS_MAX_META; $meta++){
			[$newId, ] = $idMetaUpgrader->upgrade($aliasName, $meta);
			if(isset($seenIds[$newId])){
				continue;
			}
			$seenIds[$newId] = true;

			$item = self::deserializeItemStackFromFields($newId, 0, 1, null, null);
			if($item !== null){ //unknown items (e.g. deprecated placeholders) are skipped
				$items[] = $item;
			}
		}

		if(count($items) === 0){
			return null;
		}
		return count($items) === 1 ?
			new ExactRecipeIngredient($items[0]) :
			new ComplexAliasRecipeIngredient($aliasName, $items);
	}

	/**
	 * @param mixed[] $data
	 */
	private static function deserializeNetworkIngredient(array $data) : ?RecipeIngredient{
		$type = $data["type"] ?? null;
		if(!is_string($type)){
			throw new SavedDataLoadingException("Recipe ingredient should have a string type");
		}
		$count = $data["count"] ?? 1;
		if($count !== 1){
			//every case we've seen so far where this isn't the case, it's been a bug and the count was ignored anyway
			throw new SavedDataLoadingException("Recipe inputs should have a count of exactly 1");
		}

		if($type === "item_tag"){
			if(!isset($data["itemTag"]) || !is_string($data["itemTag"])){
				throw new SavedDataLoadingException("item_tag ingredient should have a string itemTag");
			}
			return new TagWildcardRecipeIngredient($data["itemTag"]);
		}

		if($type === "complex_alias"){
			if(!isset($data["name"]) || !is_string($data["name"])){
				throw new SavedDataLoadingException("complex_alias ingredient should have a string name");
			}
			return self::deserializeComplexAliasIngredient($data["name"]);
		}

		if($type !== "default" && $type !== "name"){
			throw new SavedDataLoadingException("Unsupported recipe ingredient type \"$type\"");
		}

		if(!isset($data["itemId"]) || !is_string($data["itemId"])){
			throw new SavedDataLoadingException("default ingredient should have a string itemId");
		}
		$meta = $data["auxValue"] ?? 0;
		if(!is_int($meta)){
			throw new SavedDataLoadingException("default ingredient auxValue should be an int");
		}

		if($meta === self::NETWORK_INGREDIENT_WILDCARD_META || $meta === -1){
			//this could be an unimplemented item, but it doesn't really matter, since the item shouldn't be able to
			//be obtained anyway - filtering unknown items is only really important for outputs, to prevent players
			//obtaining them
			return new MetaWildcardRecipeIngredient($data["itemId"]);
		}

		$itemStack = self::deserializeItemStackFromFields($data["itemId"], $meta, 1, null, null);
		if($itemStack === null){
			//probably unknown item
			return null;
		}
		return new ExactRecipeIngredient($itemStack);
	}

	/**
	 * @param mixed[] $data
	 */
	private static function deserializeNetworkItemStack(array $data) : ?Item{
		if(!isset($data["id"]) || !is_string($data["id"])){
			throw new SavedDataLoadingException("Recipe output should have a string id");
		}
		$nbt = null;
		if(isset($data["nbt_b64"])){
			if(!is_string($data["nbt_b64"])){
				throw new SavedDataLoadingException("nbt_b64 should be a string");
			}
			$nbt = self::decodeNbtField($data["nbt_b64"]);
		}

		$damage = $data["damage"] ?? null;
		$count = $data["count"] ?? null;
		if(($damage !== null && !is_int($damage)) || ($count !== null && !is_int($count))){
			throw new SavedDataLoadingException("damage and count should be ints");
		}

		return self::deserializeItemStackFromFields($data["id"], $damage, $count, null, $nbt);
	}

	/**
	 * @param mixed[] $recipe
	 */
	private static function loadShapelessRecipe(CraftingManager $manager, array $recipe) : void{
		$recipeType = match($recipe["block"] ?? null){
			"crafting_table" => ShapelessRecipeType::CRAFTING,
			"stonecutter" => ShapelessRecipeType::STONECUTTER,
			"smithing_table" => ShapelessRecipeType::SMITHING,
			"cartography_table" => ShapelessRecipeType::CARTOGRAPHY,
			"furnace" => FurnaceType::FURNACE,
			"blast_furnace" => FurnaceType::BLAST_FURNACE,
			"smoker" => FurnaceType::SMOKER,
			"campfire" => FurnaceType::CAMPFIRE,
			"soul_campfire" => FurnaceType::SOUL_CAMPFIRE,
			default => null
		};
		if($recipeType === null){
			return;
		}
		if(!isset($recipe["input"]) || !is_array($recipe["input"]) || !isset($recipe["output"]) || !is_array($recipe["output"])){
			throw new SavedDataLoadingException("Shapeless recipe should have input and output lists");
		}
		$inputs = [];
		foreach($recipe["input"] as $inputData){
			if(!is_array($inputData)){
				throw new SavedDataLoadingException("Shapeless recipe input should be an object");
			}
			$input = self::deserializeNetworkIngredient($inputData);
			if($input === null){ //unknown or unsupported input
				return;
			}
			$inputs[] = $input;
		}
		$outputs = [];
		foreach($recipe["output"] as $outputData){
			if(!is_array($outputData)){
				throw new SavedDataLoadingException("Shapeless recipe output should be an object");
			}
			$output = self::deserializeNetworkItemStack($outputData);
			if($output === null){ //unknown output item
				return;
			}
			$outputs[] = $output;
		}
		//TODO: check unlocking requirements - our current system doesn't support this

		if($recipeType instanceof FurnaceType){
			if(count($inputs) !== 1 || count($outputs) !== 1){
				throw new SavedDataLoadingException("Furnace recipes must have exactly 1 input and 1 output");
			}

			$manager->getFurnaceRecipeManager($recipeType)->register(new FurnaceRecipe($outputs[0], $inputs[0]));
		}else{
			$manager->registerShapelessRecipe(new ShapelessRecipe($inputs, $outputs, $recipeType));
		}
	}

	/**
	 * @param mixed[] $recipe
	 */
	private static function loadShapedRecipe(CraftingManager $manager, array $recipe) : void{
		if(($recipe["block"] ?? null) !== "crafting_table"){ //TODO: filter others out for now to avoid breaking economics
			return;
		}
		if(!isset($recipe["input"]) || !is_array($recipe["input"]) || !isset($recipe["output"]) || !is_array($recipe["output"]) || !isset($recipe["shape"]) || !is_array($recipe["shape"])){
			throw new SavedDataLoadingException("Shaped recipe should have input, output and shape");
		}
		$inputs = [];
		foreach(Utils::promoteKeys($recipe["input"]) as $symbol => $inputData){
			if(!is_string($symbol) || !is_array($inputData)){
				throw new SavedDataLoadingException("Shaped recipe input should be a map of symbol => ingredient");
			}
			$input = self::deserializeNetworkIngredient($inputData);
			if($input === null){ //unknown or unsupported input
				return;
			}
			$inputs[$symbol] = $input;
		}
		$outputs = [];
		foreach($recipe["output"] as $outputData){
			if(!is_array($outputData)){
				throw new SavedDataLoadingException("Shaped recipe output should be an object");
			}
			$output = self::deserializeNetworkItemStack($outputData);
			if($output === null){ //unknown output item
				return;
			}
			$outputs[] = $output;
		}
		$shape = [];
		foreach($recipe["shape"] as $row){
			if(!is_string($row)){
				throw new SavedDataLoadingException("Shaped recipe shape should be a list of strings");
			}
			$shape[] = $row;
		}
		//TODO: check unlocking requirements - our current system doesn't support this
		$manager->registerShapedRecipe(new ShapedRecipe($shape, $inputs, $outputs));
	}

	/** Builds a CraftingManager from a 1.26.40+ consolidated recipes.json. */
	public static function makeFromNetworkData(string $filePath) : CraftingManager{
		$data = json_decode(Filesystem::fileGetContents($filePath), true);
		if(
			!is_array($data) ||
			!isset($data["recipes"], $data["potionMixes"], $data["containerMixes"]) ||
			!is_array($data["recipes"]) || !is_array($data["potionMixes"]) || !is_array($data["containerMixes"])
		){
			throw new SavedDataLoadingException("$filePath should contain recipes, potionMixes and containerMixes lists");
		}

		$result = new CraftingManager();

		foreach(Utils::promoteKeys($data["recipes"]) as $i => $recipe){
			if(!is_array($recipe) || !isset($recipe["type"]) || !is_int($recipe["type"])){
				throw new SavedDataLoadingException("Invalid recipe at index $i: expected object with int type");
			}
			try{
				switch($recipe["type"]){
					case self::NETWORK_RECIPE_TYPE_SHAPELESS:
					case self::NETWORK_RECIPE_TYPE_USER_DATA_SHAPELESS:
						self::loadShapelessRecipe($result, $recipe);
						break;
					case self::NETWORK_RECIPE_TYPE_SHAPED:
						self::loadShapedRecipe($result, $recipe);
						break;
					case self::NETWORK_RECIPE_TYPE_MULTI:
					case self::NETWORK_RECIPE_TYPE_SMITHING_TRANSFORM:
					case self::NETWORK_RECIPE_TYPE_SMITHING_TRIM:
						//TODO: not supported by the crafting system yet
						break;
				}
			}catch(SavedDataLoadingException $e){
				throw new SavedDataLoadingException("Invalid recipe at index $i: " . $e->getMessage(), 0, $e);
			}
		}

		foreach(Utils::promoteKeys($data["potionMixes"]) as $i => $mix){
			if(
				!is_array($mix) ||
				!isset($mix["inputId"], $mix["inputMeta"], $mix["reagentId"], $mix["reagentMeta"], $mix["outputId"], $mix["outputMeta"]) ||
				!is_string($mix["inputId"]) || !is_int($mix["inputMeta"]) ||
				!is_string($mix["reagentId"]) || !is_int($mix["reagentMeta"]) ||
				!is_string($mix["outputId"]) || !is_int($mix["outputMeta"])
			){
				throw new SavedDataLoadingException("Invalid potion mix at index $i");
			}
			$input = self::deserializeItemStackFromFields($mix["inputId"], $mix["inputMeta"], 1, null, null);
			$reagent = self::deserializeItemStackFromFields($mix["reagentId"], $mix["reagentMeta"], 1, null, null);
			$output = self::deserializeItemStackFromFields($mix["outputId"], $mix["outputMeta"], 1, null, null);
			if($input === null || $reagent === null || $output === null){
				continue;
			}
			$result->registerPotionTypeRecipe(new PotionTypeRecipe(
				new ExactRecipeIngredient($input),
				new ExactRecipeIngredient($reagent),
				$output
			));
		}

		foreach(Utils::promoteKeys($data["containerMixes"]) as $i => $mix){
			if(
				!is_array($mix) ||
				!isset($mix["inputId"], $mix["reagentId"], $mix["outputId"]) ||
				!is_string($mix["inputId"]) || !is_string($mix["reagentId"]) || !is_string($mix["outputId"])
			){
				throw new SavedDataLoadingException("Invalid container mix at index $i");
			}
			$reagent = self::deserializeItemStackFromFields($mix["reagentId"], null, 1, null, null);
			//TODO: this is a really awful way to just check if an ID is recognized ...
			if(
				$reagent === null ||
				self::deserializeItemStackFromFields($mix["inputId"], null, 1, null, null) === null ||
				self::deserializeItemStackFromFields($mix["outputId"], null, 1, null, null) === null
			){
				//unknown item
				continue;
			}
			$result->registerPotionContainerChangeRecipe(new PotionContainerChangeRecipe(
				$mix["inputId"],
				new ExactRecipeIngredient($reagent),
				$mix["outputId"]
			));
		}

		return $result;
	}
}
