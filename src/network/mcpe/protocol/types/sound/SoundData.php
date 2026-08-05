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

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

abstract class SoundData{

	abstract public function getEvent() : SoundDataEvent;

	public static function read(PacketSerializer $in) : self{
		$event = SoundDataEvent::fromPacket($in->getUnsignedVarInt());
		return match($event){
			SoundDataEvent::STOP => new StopSoundData(),
			SoundDataEvent::SET_VOLUME => new SetVolumeSoundData($in->getLFloat()),
			SoundDataEvent::SET_PITCH => new SetPitchSoundData($in->getLFloat()),
			SoundDataEvent::FADE => new FadeSoundData($in->getLFloat(), $in->getLFloat()),
			SoundDataEvent::SEEK_TO => new SeekToSoundData($in->getLFloat()),
			SoundDataEvent::PAUSE => new PauseSoundData(),
			SoundDataEvent::RESUME => new ResumeSoundData(),
		};
	}

	public function write(PacketSerializer $out) : void{
		$out->putUnsignedVarInt($this->getEvent()->value);
		$this->writeData($out);
	}

	protected function writeData(PacketSerializer $out) : void{
		//NOOP
	}
}
