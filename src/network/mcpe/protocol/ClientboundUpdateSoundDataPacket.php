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
use pocketmine\network\mcpe\protocol\types\sound\SoundData;

class ClientboundUpdateSoundDataPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_UPDATE_SOUND_DATA_PACKET;

	private int $serverSoundHandle;
	private string $soundEvent = "";

	private ?SoundData $stop = null;
	private ?SoundData $setVolume = null;
	private ?SoundData $setPitch = null;
	private ?SoundData $fade = null;
	private ?SoundData $seekTo = null;
	private ?SoundData $pause = null;
	private ?SoundData $resume = null;

	/**
	 * @generate-create-func
	 */
	public static function create(int $serverSoundHandle, string $soundEvent) : self{
		$result = new self();
		$result->serverSoundHandle = $serverSoundHandle;
		$result->soundEvent = $soundEvent;
		return $result;
	}

	public static function createWithSoundData(
		int $serverSoundHandle,
		?SoundData $stop = null,
		?SoundData $setVolume = null,
		?SoundData $setPitch = null,
		?SoundData $fade = null,
		?SoundData $seekTo = null,
		?SoundData $pause = null,
		?SoundData $resume = null,
	) : self{
		$result = new self();
		$result->serverSoundHandle = $serverSoundHandle;
		$result->stop = $stop;
		$result->setVolume = $setVolume;
		$result->setPitch = $setPitch;
		$result->fade = $fade;
		$result->seekTo = $seekTo;
		$result->pause = $pause;
		$result->resume = $resume;
		return $result;
	}

	public function getServerSoundHandle() : int{ return $this->serverSoundHandle; }

	public function getSoundEvent() : string{ return $this->soundEvent; }

	public function getStop() : ?SoundData{ return $this->stop; }

	public function getSetVolume() : ?SoundData{ return $this->setVolume; }

	public function getSetPitch() : ?SoundData{ return $this->setPitch; }

	public function getFade() : ?SoundData{ return $this->fade; }

	public function getSeekTo() : ?SoundData{ return $this->seekTo; }

	public function getPause() : ?SoundData{ return $this->pause; }

	public function getResume() : ?SoundData{ return $this->resume; }

	protected function decodePayload(PacketSerializer $in) : void{
		$this->serverSoundHandle = $in->getLLong();
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->stop = $in->readOptional(fn() => SoundData::read($in));
			$this->setVolume = $in->readOptional(fn() => SoundData::read($in));
			$this->setPitch = $in->readOptional(fn() => SoundData::read($in));
			$this->fade = $in->readOptional(fn() => SoundData::read($in));
			$this->seekTo = $in->readOptional(fn() => SoundData::read($in));
			$this->pause = $in->readOptional(fn() => SoundData::read($in));
			$this->resume = $in->readOptional(fn() => SoundData::read($in));
			return;
		}
		$this->soundEvent = $in->getString();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putLLong($this->serverSoundHandle);
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_40){
			$writer = fn(SoundData $data) => $data->write($out);
			$out->writeOptional($this->stop, $writer);
			$out->writeOptional($this->setVolume, $writer);
			$out->writeOptional($this->setPitch, $writer);
			$out->writeOptional($this->fade, $writer);
			$out->writeOptional($this->seekTo, $writer);
			$out->writeOptional($this->pause, $writer);
			$out->writeOptional($this->resume, $writer);
			return;
		}
		$out->putString($this->soundEvent);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundUpdateSoundData($this);
	}
}
