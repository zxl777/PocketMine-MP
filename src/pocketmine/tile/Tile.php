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

/**
 * All the Tile classes and related classes
 * TODO: Add Nether Reactor tile
 */
namespace pocketmine\tile;

use pocketmine\event\Timings;
use pocketmine\level\format\Chunk;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Int;

abstract class Tile extends Position{
	const SIGN = "Sign";
	const CHEST = "Chest";
	const FURNACE = "Furnace";

	public static $tileCount = 1;

	/** @var Chunk */
	public $chunk;
	public $name;
	public $id;
	public $x;
	public $y;
	public $z;
	public $attach;
	public $metadata;
	public $closed;
	public $namedtag;
	protected $lastUpdate;
	protected $server;
	protected $timings;

	/** @var \pocketmine\event\TimingsHandler */
	public $tickTimer;

	public function __construct(FullChunk $chunk, Compound $nbt){
		if($chunk === null or $chunk->getProvider() === null){
			throw new \Exception("Invalid garbage Chunk given to Tile");
		}

		$this->timings = Timings::getTileEntityTimings($this);

		$this->server = $chunk->getProvider()->getLevel()->getServer();
		$this->chunk = $chunk;
		$this->setLevel($chunk->getProvider()->getLevel());
		$this->namedtag = $nbt;
		$this->closed = false;
		$this->name = "";
		$this->lastUpdate = microtime(true);
		$this->id = Tile::$tileCount++;
		$this->x = (int) $this->namedtag["x"];
		$this->y = (int) $this->namedtag["y"];
		$this->z = (int) $this->namedtag["z"];

		$this->chunk->addTile($this);
		$this->getLevel()->addTile($this);
		$this->tickTimer = Timings::getTileEntityTimings($this);
	}

	public function getID(){
		return $this->id;
	}

	public function saveNBT(){
		$this->namedtag->x = new Int("x", $this->x);
		$this->namedtag->y = new Int("y", $this->y);
		$this->namedtag->z = new Int("z", $this->z);
	}

	/**
	 * @return \pocketmine\block\Block
	 */
	public function getBlock(){
		return $this->level->getBlock($this);
	}

	public function onUpdate(){
		return false;
	}

	public final function scheduleUpdate(){
		$this->level->updateTiles[$this->id] = $this;
	}

	public function __destruct(){
		$this->close();
	}

	public function close(){
		if($this->closed === false){
			$this->closed = true;
			unset($this->level->updateTiles[$this->id]);
			if($this->chunk instanceof FullChunk){
				$this->chunk->removeTile($this);
			}
			if(($level = $this->getLevel()) instanceof Level){
				$level->removeTile($this);
			}
			$this->level = null;
		}
	}

	public function getName(){
		return $this->name;
	}

}
