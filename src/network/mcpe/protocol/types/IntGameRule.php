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

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

final class IntGameRule extends GameRule{
	use GetTypeIdFromConstTrait;

	public const ID = GameRuleType::INT;

	private int $value;

	public function __construct(int $value, bool $isPlayerModifiable){
		parent::__construct($isPlayerModifiable);
		$this->value = $value;
	}

	public function getValue() : int{
		return $this->value;
	}

	/** >= PROTOCOL_1_26_40 dropped the StartGame special case - int rules are LE ints everywhere */
	private static function isVarInt(PacketSerializer $stream, bool $isStartGame) : bool{
		if($stream->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_40){
			return false;
		}
		return $isStartGame || $stream->getProtocolId() < ProtocolInfo::PROTOCOL_1_21_111;
	}

	public function encode(PacketSerializer $out, bool $isStartGame = false) : void{
		if(self::isVarInt($out, $isStartGame)){
			$out->putUnsignedVarInt($this->value);
		}else{
			$out->putLInt($this->value);
		}
	}

	public static function decode(PacketSerializer $in, bool $isPlayerModifiable, bool $isStartGame = false) : self{
		return new self(self::isVarInt($in, $isStartGame) ? $in->getUnsignedVarInt() : $in->getLInt(), $isPlayerModifiable);
	}
}
