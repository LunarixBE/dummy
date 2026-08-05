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

class SendPartyDestinationCookiePacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::SEND_PARTY_DESTINATION_COOKIE_PACKET;

	private string $cookie;
	/** >= PROTOCOL_1_26_40, sent as a byte */
	public const INTENT_NOTIFY = 0;
	public const INTENT_OPT_IN = 1;
	public const INTENT_OPT_OUT = 2;

	private string $intent;
	private string $destinationName;

	/**
	 * @generate-create-func
	 */
	public static function create(string $cookie, string $intent, string $destinationName) : self{
		$result = new self();
		$result->cookie = $cookie;
		$result->intent = $intent;
		$result->destinationName = $destinationName;
		return $result;
	}

	public function getCookie() : string{ return $this->cookie; }

	public function getIntent() : string{ return $this->intent; }

	public function getDestinationName() : string{ return $this->destinationName; }

	protected function decodePayload(PacketSerializer $in) : void{
		$this->cookie = $in->getString();
		//kept as a string so the field type stays stable across protocols
		$this->intent = $in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_40 ? (string) $in->getByte() : $in->getString();
		$this->destinationName = $in->getString();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putString($this->cookie);
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_26_40){
			$out->putByte((int) $this->intent);
		}else{
			$out->putString($this->intent);
		}
		$out->putString($this->destinationName);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSendPartyDestinationCookie($this);
	}
}
