<?php
namespace Szurubooru\Dao;

abstract class AbstractDao implements ICrudDao
{
	protected $db;
	protected $collection;
	protected $entityName;
	protected $entityConverter;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		$collectionName,
		$entityName)
	{
		$this->entityConverter = new EntityConverter($entityName);
		$this->db = $databaseConnection->getDatabase();
		$this->collection = $this->db->selectCollection($collectionName);
		$this->entityName = $entityName;
	}

	public function save(&$entity)
	{
		$arrayEntity = $this->entityConverter->toArray($entity);
		if ($entity->id)
		{
			unset ($arrayEntity['_id']);
			$this->collection->update(['_id' => new \MongoId($entity->id)], $arrayEntity, ['safe' => true]);
		}
		else
		{
			$this->collection->insert($arrayEntity, ['safe' => true]);
		}
		$entity = $this->entityConverter->toEntity($arrayEntity);
		return $entity;
	}

	public function getAll()
	{
		$entities = [];
		foreach ($this->collection->find() as $key => $arrayEntity)
		{
			$entity = $this->entityConverter->toEntity($arrayEntity);
			$entities[$key] = $entity;
		}
		return $entities;
	}

	public function getById($postId)
	{
		$arrayEntity = $this->collection->findOne(['_id' => new \MongoId($postId)]);
		return $this->entityConverter->toEntity($arrayEntity);
	}

	public function deleteAll()
	{
		$this->collection->remove();
	}

	public function deleteById($postId)
	{
		$this->collection->remove(['_id' => new \MongoId($postId)]);
	}
}