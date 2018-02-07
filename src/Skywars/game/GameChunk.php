<?php

namespace Skywars\game;

use pocketmine\level\format\LevelProvider;
use pocketmine\level\format\mcregion\McRegion;
use pocketmine\nbt\tag\ByteTag;

/**
 * Extra GameChunk overrides default Chunk class with additional options
 */
class GameChunk extends \pocketmine\level\format\mcregion\Chunk {

	/**
	 * Save options for chunk
	 * 
	 * @param int $x
	 * @param int $z
	 * @param $blocks
	 * @param $data
	 * @param $skyLight
	 * @param type $blockLight
	 * @param type $colors
	 * @param type $heightMap
	 * @param LevelProvider $provider
	 * @return \Skywars\game\GameChunk
	 */
	public static function fromData($x, $z, $blocks, $data, $skyLight, $blockLight, $colors, $heightMap, LevelProvider $provider = null) {
		$chunk = new GameChunk($provider instanceof LevelProvider ? $provider : McRegion::class, null);
		$chunk->provider = $provider;
		$chunk->x = $x;
		$chunk->z = $z;

		$chunk->blocks = $blocks;
		$chunk->data = $data;
		$chunk->skyLight = $skyLight;
		$chunk->blockLight = $blockLight;
		$chunk->allowUnload = false;

		$chunk->heightMap = $heightMap;
		$chunk->biomeColors = $colors;

		$chunk->nbt->TerrainGenerated = new ByteTag("TerrainGenerated", 1);
		$chunk->nbt->TerrainPopulated = new ByteTag("TerrainPopulated", 1);
		$chunk->nbt->LightPopulated = new ByteTag("LightPopulated", 1);

		return $chunk;
	}

}
