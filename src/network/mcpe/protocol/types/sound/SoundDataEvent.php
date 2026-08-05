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

namespace pocketmine\network\mcpe\protocol\types\sound;

use pocketmine\network\mcpe\protocol\types\PacketIntEnumTrait;

/**
 * >= PROTOCOL_1_26_40, carried by ClientboundUpdateSoundDataPacket
 */
enum SoundDataEvent : int{
	use PacketIntEnumTrait;

	case STOP = 0;
	case SET_VOLUME = 1;
	case SET_PITCH = 2;
	case FADE = 3;
	case SEEK_TO = 4;
	case PAUSE = 5;
	case RESUME = 6;
}
