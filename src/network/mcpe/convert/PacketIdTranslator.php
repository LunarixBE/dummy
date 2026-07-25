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

namespace pocketmine\network\mcpe\convert;

use pocketmine\network\mcpe\protocol\ProtocolInfo;

/**
 * Translates current core packet IDs to legacy Bedrock protocol packet IDs.
 *
 * @internal
 */
final class PacketIdTranslator{
	/**
	 * @var int[]
	 * @phpstan-var array<int, int>
	 */
	private const R12_TO_CURRENT = [
		0x01 => ProtocolInfo::LOGIN_PACKET,
		0x02 => ProtocolInfo::PLAY_STATUS_PACKET,
		0x03 => ProtocolInfo::SERVER_TO_CLIENT_HANDSHAKE_PACKET,
		0x04 => ProtocolInfo::CLIENT_TO_SERVER_HANDSHAKE_PACKET,
		0x05 => ProtocolInfo::DISCONNECT_PACKET,
		0x06 => ProtocolInfo::RESOURCE_PACKS_INFO_PACKET,
		0x07 => ProtocolInfo::RESOURCE_PACK_STACK_PACKET,
		0x08 => ProtocolInfo::RESOURCE_PACK_CLIENT_RESPONSE_PACKET,
		0x09 => ProtocolInfo::TEXT_PACKET,
		0x0a => ProtocolInfo::SET_TIME_PACKET,
		0x0b => ProtocolInfo::START_GAME_PACKET,
		0x0c => ProtocolInfo::ADD_PLAYER_PACKET,
		0x0d => ProtocolInfo::ADD_ACTOR_PACKET,
		0x0e => ProtocolInfo::REMOVE_ACTOR_PACKET,
		0x0f => ProtocolInfo::ADD_ITEM_ACTOR_PACKET,
		0x11 => ProtocolInfo::TAKE_ITEM_ACTOR_PACKET,
		0x12 => ProtocolInfo::MOVE_ACTOR_ABSOLUTE_PACKET,
		0x13 => ProtocolInfo::MOVE_PLAYER_PACKET,
		0x14 => ProtocolInfo::PASSENGER_JUMP_PACKET,
		0x15 => ProtocolInfo::UPDATE_BLOCK_PACKET,
		0x16 => ProtocolInfo::ADD_PAINTING_PACKET,
		0x18 => ProtocolInfo::LEVEL_SOUND_EVENT_PACKET_V1,
		0x19 => ProtocolInfo::LEVEL_EVENT_PACKET,
		0x1a => ProtocolInfo::BLOCK_EVENT_PACKET,
		0x1b => ProtocolInfo::ACTOR_EVENT_PACKET,
		0x1c => ProtocolInfo::MOB_EFFECT_PACKET,
		0x1d => ProtocolInfo::UPDATE_ATTRIBUTES_PACKET,
		0x1e => ProtocolInfo::INVENTORY_TRANSACTION_PACKET,
		0x1f => ProtocolInfo::MOB_EQUIPMENT_PACKET,
		0x20 => ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET,
		0x21 => ProtocolInfo::INTERACT_PACKET,
		0x22 => ProtocolInfo::BLOCK_PICK_REQUEST_PACKET,
		0x23 => ProtocolInfo::ACTOR_PICK_REQUEST_PACKET,
		0x24 => ProtocolInfo::PLAYER_ACTION_PACKET,
		0x25 => ProtocolInfo::ACTOR_FALL_PACKET,
		0x26 => ProtocolInfo::HURT_ARMOR_PACKET,
		0x27 => ProtocolInfo::SET_ACTOR_DATA_PACKET,
		0x28 => ProtocolInfo::SET_ACTOR_MOTION_PACKET,
		0x29 => ProtocolInfo::SET_ACTOR_LINK_PACKET,
		0x2a => ProtocolInfo::SET_HEALTH_PACKET,
		0x2b => ProtocolInfo::SET_SPAWN_POSITION_PACKET,
		0x2c => ProtocolInfo::ANIMATE_PACKET,
		0x2d => ProtocolInfo::RESPAWN_PACKET,
		0x2e => ProtocolInfo::CONTAINER_OPEN_PACKET,
		0x2f => ProtocolInfo::CONTAINER_CLOSE_PACKET,
		0x30 => ProtocolInfo::PLAYER_HOTBAR_PACKET,
		0x31 => ProtocolInfo::INVENTORY_CONTENT_PACKET,
		0x32 => ProtocolInfo::INVENTORY_SLOT_PACKET,
		0x33 => ProtocolInfo::CONTAINER_SET_DATA_PACKET,
		0x34 => ProtocolInfo::CRAFTING_DATA_PACKET,
		0x35 => ProtocolInfo::CRAFTING_EVENT_PACKET,
		0x36 => ProtocolInfo::GUI_DATA_PICK_ITEM_PACKET,
		0x37 => ProtocolInfo::ADVENTURE_SETTINGS_PACKET,
		0x38 => ProtocolInfo::BLOCK_ACTOR_DATA_PACKET,
		0x39 => ProtocolInfo::PLAYER_INPUT_PACKET,
		0x3a => ProtocolInfo::LEVEL_CHUNK_PACKET,
		0x3b => ProtocolInfo::SET_COMMANDS_ENABLED_PACKET,
		0x3c => ProtocolInfo::SET_DIFFICULTY_PACKET,
		0x3d => ProtocolInfo::CHANGE_DIMENSION_PACKET,
		0x3e => ProtocolInfo::SET_PLAYER_GAME_TYPE_PACKET,
		0x3f => ProtocolInfo::PLAYER_LIST_PACKET,
		0x40 => ProtocolInfo::SIMPLE_EVENT_PACKET,
		0x41 => ProtocolInfo::LEGACY_TELEMETRY_EVENT_PACKET,
		0x42 => ProtocolInfo::SPAWN_EXPERIENCE_ORB_PACKET,
		0x43 => ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET,
		0x44 => ProtocolInfo::MAP_INFO_REQUEST_PACKET,
		0x45 => ProtocolInfo::REQUEST_CHUNK_RADIUS_PACKET,
		0x46 => ProtocolInfo::CHUNK_RADIUS_UPDATED_PACKET,
		0x47 => ProtocolInfo::ITEM_FRAME_DROP_ITEM_PACKET,
		0x48 => ProtocolInfo::GAME_RULES_CHANGED_PACKET,
		0x49 => ProtocolInfo::CAMERA_PACKET,
		0x4a => ProtocolInfo::BOSS_EVENT_PACKET,
		0x4b => ProtocolInfo::SHOW_CREDITS_PACKET,
		0x4c => ProtocolInfo::AVAILABLE_COMMANDS_PACKET,
		0x4d => ProtocolInfo::COMMAND_REQUEST_PACKET,
		0x4e => ProtocolInfo::COMMAND_BLOCK_UPDATE_PACKET,
		0x4f => ProtocolInfo::COMMAND_OUTPUT_PACKET,
		0x50 => ProtocolInfo::UPDATE_TRADE_PACKET,
		0x51 => ProtocolInfo::UPDATE_EQUIP_PACKET,
		0x52 => ProtocolInfo::RESOURCE_PACK_DATA_INFO_PACKET,
		0x53 => ProtocolInfo::RESOURCE_PACK_CHUNK_DATA_PACKET,
		0x54 => ProtocolInfo::RESOURCE_PACK_CHUNK_REQUEST_PACKET,
		0x55 => ProtocolInfo::TRANSFER_PACKET,
		0x56 => ProtocolInfo::PLAY_SOUND_PACKET,
		0x57 => ProtocolInfo::STOP_SOUND_PACKET,
		0x58 => ProtocolInfo::SET_TITLE_PACKET,
		0x59 => ProtocolInfo::ADD_BEHAVIOR_TREE_PACKET,
		0x5a => ProtocolInfo::STRUCTURE_BLOCK_UPDATE_PACKET,
		0x5b => ProtocolInfo::SHOW_STORE_OFFER_PACKET,
		0x5c => ProtocolInfo::PURCHASE_RECEIPT_PACKET,
		0x5d => ProtocolInfo::PLAYER_SKIN_PACKET,
		0x5e => ProtocolInfo::SUB_CLIENT_LOGIN_PACKET,
		0x5f => ProtocolInfo::AUTOMATION_CLIENT_CONNECT_PACKET,
		0x60 => ProtocolInfo::SET_LAST_HURT_BY_PACKET,
		0x61 => ProtocolInfo::BOOK_EDIT_PACKET,
		0x62 => ProtocolInfo::NPC_REQUEST_PACKET,
		0x63 => ProtocolInfo::PHOTO_TRANSFER_PACKET,
		0x64 => ProtocolInfo::MODAL_FORM_REQUEST_PACKET,
		0x65 => ProtocolInfo::MODAL_FORM_RESPONSE_PACKET,
		0x66 => ProtocolInfo::SERVER_SETTINGS_REQUEST_PACKET,
		0x67 => ProtocolInfo::SERVER_SETTINGS_RESPONSE_PACKET,
		0x68 => ProtocolInfo::SHOW_PROFILE_PACKET,
		0x69 => ProtocolInfo::SET_DEFAULT_GAME_TYPE_PACKET,
		0x6a => ProtocolInfo::REMOVE_OBJECTIVE_PACKET,
		0x6b => ProtocolInfo::SET_DISPLAY_OBJECTIVE_PACKET,
		0x6c => ProtocolInfo::SET_SCORE_PACKET,
		0x6d => ProtocolInfo::LAB_TABLE_PACKET,
		0x6e => ProtocolInfo::UPDATE_BLOCK_SYNCED_PACKET,
		0x6f => ProtocolInfo::MOVE_ACTOR_DELTA_PACKET,
		0x70 => ProtocolInfo::SET_SCOREBOARD_IDENTITY_PACKET,
		0x71 => ProtocolInfo::SET_LOCAL_PLAYER_AS_INITIALIZED_PACKET,
		0x72 => ProtocolInfo::UPDATE_SOFT_ENUM_PACKET,
		0x73 => ProtocolInfo::NETWORK_STACK_LATENCY_PACKET,
		0x76 => ProtocolInfo::SPAWN_PARTICLE_EFFECT_PACKET,
		0x77 => ProtocolInfo::AVAILABLE_ACTOR_IDENTIFIERS_PACKET,
		0x78 => ProtocolInfo::LEVEL_SOUND_EVENT_PACKET_V2,
		0x79 => ProtocolInfo::NETWORK_CHUNK_PUBLISHER_UPDATE_PACKET,
		0x7a => ProtocolInfo::BIOME_DEFINITION_LIST_PACKET,
		0x7b => ProtocolInfo::LEVEL_SOUND_EVENT_PACKET,
		0x7c => ProtocolInfo::LEVEL_EVENT_GENERIC_PACKET,
		0x7d => ProtocolInfo::LECTERN_UPDATE_PACKET,
		0x7f => ProtocolInfo::ADD_ENTITY_PACKET,
		0x80 => ProtocolInfo::REMOVE_ENTITY_PACKET,
		0x81 => ProtocolInfo::CLIENT_CACHE_STATUS_PACKET,
		0x82 => ProtocolInfo::ON_SCREEN_TEXTURE_ANIMATION_PACKET,
		0x83 => ProtocolInfo::MAP_CREATE_LOCKED_COPY_PACKET,
		0x84 => ProtocolInfo::STRUCTURE_TEMPLATE_DATA_REQUEST_PACKET,
		0x85 => ProtocolInfo::STRUCTURE_TEMPLATE_DATA_RESPONSE_PACKET,
		0x87 => ProtocolInfo::CLIENT_CACHE_BLOB_STATUS_PACKET,
		0x88 => ProtocolInfo::CLIENT_CACHE_MISS_RESPONSE_PACKET,
	];

	/**
	 * @var int[]|null
	 * @phpstan-var array<int, int>|null
	 */
	private static ?array $currentToR12 = null;

	private function __construct(){
		//NOOP
	}

	public static function isLegacyProtocol(int $protocolId) : bool{
		return $protocolId === ProtocolInfo::PROTOCOL_1_12_0;
	}

	/**
	 * @return int[]
	 * @phpstan-return array<int, int>
	 */
	private static function getCurrentToR12() : array{
		return self::$currentToR12 ??= array_flip(self::R12_TO_CURRENT);
	}

	public static function toNetworkId(int $protocolId, int $currentId) : int{
		if(!self::isLegacyProtocol($protocolId)){
			return $currentId;
		}
		return self::getCurrentToR12()[$currentId] ?? throw new \UnexpectedValueException("Packet ID $currentId is not supported by Minecraft 1.12.x");
	}

	public static function fromNetworkId(int $protocolId, int $networkId) : ?int{
		if(!self::isLegacyProtocol($protocolId)){
			return $networkId;
		}
		return self::R12_TO_CURRENT[$networkId] ?? null;
	}
}
