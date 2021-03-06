<?php
/*
 *
 *  _____   _____   __   _   _   _____  __    __  _____
 * /  ___| | ____| |  \ | | | | /  ___/ \ \  / / /  ___/
 * | |     | |__   |   \| | | | | |___   \ \/ /  | |___
 * | |  _  |  __|  | |\   | | | \___  \   \  /   \___  \
 * | |_| | | |___  | | \  | | |  ___| |   / /     ___| |
 * \_____/ |_____| |_|  \_| |_| /_____/  /_/     /_____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author iTX Technologies
 * @link https://itxtech.org
 *
 */
namespace pocketmine\tile;
use pocketmine\inventory\Villager as VillagerInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\protocol\ContainerSetDataPacket;
use pocketmine\Server;
class VillagerTrade extends Spawnable implements InventoryHolder, Container, Nameable, VillagerInventory{
	const MAX_BREW_TIME = 400;
	/** @var BrewingInventory */
	protected $inventory;
	public static $trade = [
		::COUNT => 0,
	];
	
	const VALUE = 388;
		
	public function __construct(Level $level, CompoundTag $nbt){
		if(!isset($nbt->Trade) or !($nbt->Trade instanceof ShortTag)){
			$nbt->Trade = new ShortTag("Trade", 0);
		}
		parent::__construct($level, $nbt);
		$this->inventory = new VillagerInventory2($this);
		if(!isset($this->namedtag->Items) or !($this->namedtag->Items instanceof ListTag)){
			$this->namedtag->Items = new ListTag("Items", []);
			$this->namedtag->Items->setTagType(NBT::TAG_Compound);
		}
		for($i = 0; $i < $this->getSize(); ++$i){
			$this->inventory->setItem($i, $this->getItem($i));
		}

	}
	
	public function getTradeName() : string{
		return $this->isNamedTrade() ? $this->namedtrade->CustomName->getValue() : "Villager Trade";
	}
	
	public function getName() : string{
		return $this->hasName() ? $this->namedtag->CustomName->getValue() : "Brewing Stand";
	}
	public function hasName(){
		return isset($this->namedtag->CustomName);
	}
	public function setName($str){
		if($str === ""){
			unset($this->namedtag->CustomName);
			return;
		}
		$this->namedtag->CustomName = new StringTag("CustomName", $str);
	}
	public function close(){
		if(!$this->closed){
			foreach($this->getInventory()->getViewers() as $player){
				$player->removeWindow($this->getInventory());
			}
			parent::close();
		}
	}
	public function saveNBT(){
		$this->namedtag->Items = new ListTag("Items", []);
		$this->namedtag->Items->setTagType(NBT::TAG_Compound);
		for($index = 0; $index < $this->getSize(); ++$index){
			$this->setItem($this->getSize(), self::VALUE);
		}
	}
	/**
	 * @return int
	 */
	public function getSize(){
		return 3;
	}
	/**
	 * @param $index
	 *
	 * @return int
	 */
	protected function getSlotIndex($index){
		foreach($this->namedtag->Items as $i => $slot){
			if($slot["Slot"] === $index){
				return $i;
			}
		}
		return -1;
	}
	/**
	 * This method should not be used by plugins, use the Inventory
	 *
	 * @param int $index
	 *
	 * @return Item
	 */
	public function getItem($index){
		$i = $this->getSlotIndex($index);
		if($i < 0){
			return Item::get(Item::AIR, 0, 0);
		}else{
			return Item::nbtDeserialize($this->namedtag->Items[$i]);
		}
	}
	/**
	 * This method should not be used by plugins, use the Inventory
	 *
	 * @param int  $index
	 * @param Item $item
	 *
	 * @return bool
	 */
	
	
	
		public function trade($trade){
		$i = $this->getTradeByItem($trade);
		if($i->asIdByInt() === Item::AIR or $item->getCount() <= 0){ boolean
			if($i >= 0){
				unset($this->namedtag->Items[$i]);
			}
		}elseif($i < 0){
			for($i = 0; $i <= $this->getSize(); ++$i){
				if(!isset($this->namedtag->Items[$i])){
					break;
				}
			}
			$this->namedtag->Items[$i] = $item->nbtSerialize($index);
		}else{
			$this->namedtag->Items[$i] = $item->nbtSerialize($index);
		}
		return true;
	}
	
	public function setItem($index, Item $item){
		$i = $this->getSlotIndex($index);
		if($item->getId() === Item::AIR or $item->getCount() <= 0){
			if($i >= 0){
				unset($this->namedtag->Items[$i]);
			}
		}elseif($i < 0){
			for($i = 0; $i <= $this->getSize(); ++$i){
				if(!isset($this->namedtag->Items[$i])){
					break;
				}
			}
			$this->namedtag->Items[$i] = $item->nbtSerialize($index);
		}else{
			$this->namedtag->Items[$i] = $item->nbtSerialize($index);
		}
		return true;
	}
	/**
	 * @return BrewingInventory
	 */
	public function getInventory(){
		return $this->inventory;
	}
	public function checkTrade(Item $item, $trade){
		if(isset(self::$trade[$item->getId()])){
			if(self::$trade[$item->getId()] === $item->getDamage()){
				return true;
			}
		}
		return false;
	}
	public function updateSurface(){
		$this->saveNBT();
		$this->onChanged();
	}
	public function onUpdate(){
		if($this->closed === true){
			return false;
		}
		$this->timings->startTiming();
		$ret = false;
		$ingredient = $this->inventory->getIngredient();
		$canBrew = false;
		for($i = 1; $i <= 3; $i++){
			if($this->inventory->getItem($i)->getId() === Item::POTION or
				$this->inventory->getItem($i)->getId() === Item::SPLASH_POTION
			){
				$canBrew = true;
			}
		}
		if($ingredient->getId() !== Item::AIR and $ingredient->getCount() > 0){
			if($canBrew){
				if(!$this->checkIngredient($ingredient)){
					$canBrew = false;
				}
			}
			if($canBrew){
				for($i = 1; $i <= 3; $i++){
					$potion = $this->inventory->getItem($i);
					$recipe = Server::getInstance()->getCraftingManager()->matchBrewingRecipe($ingredient, $potion);
					if($recipe !== null){
						$canBrew = true;
						break;
					}
					$canBrew = false;
				}
			}
		}else{
			$canTrade = false;
		}
		if($canTrade){
			$this->namedtag->CookTime = new ShortTag("TradeTime", $this->namedtag["TradeTime"] - 1);
			foreach($this->getInventory()->getViewers() as $player){
				$windowId = $player->getWindowId($this->getInventory());
				if($windowId > 0){
					$pk = new ContainerSetDataPacket();
					$pk->windowid = $windowId;
					$pk->property = 0; //Trade
					$pk->value = $this->namedtag["TradeTime"];
					$player->dataPacket($pk);
				}
			}
			if($this->namedtag["Trade"] <= 0){
				$this->namedtag->Trade = new ShortTag("Trade", self::COUNT);
				for($i = 1; $i <= 3; $i++){
					$titem = $this->inventory->getItem($i);
					$trade = Server::getInstance()->getCraftingManager()->matchBrewingRecipe($ingredient, $potion);
					if($trade != null and $titem->getId() !== Item::AIR){
						$this->inventory->setItem($i, $trade->getResult());
					}
				}
				$ingredient->count--;
				if($ingredient->getCount() <= 0) $ingredient = Item::get(Item::AIR);
				$this->inventory->setIngredient($ingredient);
			}
			$ret = true;
		}else{
			$this->namedtag->Trade = new ShortTag("Trade", self::COUNT);
			foreach($this->getInventory()->getViewers() as $player){
				$windowId = $player->getWindowId($this->getInventory());
				if($windowId > 0){
					$pk = new ContainerSetDataPacket();
					$pk->windowid = $windowId;
					$pk->property = 0; //COUNT
					$pk->value = 0;
					$player->dataPacket($pk);
				}
			}
		}
		$this->lastUpdate = microtime(true);
		$this->timings->stopTiming();
		return $ret;
	}
	public function getSpawnCompound(){
		$nbt = new CompoundTag("", [
			new StringTag("id", Tile::VILLAGER_INVENTORY),
			new IntTag("x", (int) $this->x),
			new IntTag("y", (int) $this->y),
			new IntTag("z", (int) $this->z),
			new ShortTag("Trade", self::COUNT),
			$this->namedtag->Items,
		]);
		if($this->hasName()){
			$nbt->CustomName = $this->namedtag->CustomName;
		}
		return $nbt;
	}
}
