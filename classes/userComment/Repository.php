<?php

namespace APP\plugins\generic\userComments\classes\userComment;

use APP\core\Request;
use PKP\services\PKPSchemaService;
use APP\plugins\generic\userComments\classes\maps\Schema;

class Repository
{
    public $dao;

    protected $request;

    protected $schemaService;

    public function __construct(DAO $dao, Request $request, PKPSchemaService $schemaService)
    {
        $this->dao = $dao;
        $this->request = $request;
        $this->schemaService = $schemaService;
    }

    public function newDataObject(array $params = []): UserComment
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }

    public function exists(int $id, int $contextId = null): bool
    {
        return $this->dao->exists($id, $contextId);
    }

    public function get(int $id, int $contextId = null): ?UserComment
    {
        return $this->dao->get($id, $contextId);
    }

    public function add(UserComment $UserComment): int
    {
        $id = $this->dao->insert($UserComment);
        return $id;
    }

    public function edit(UserComment $UserComment, array $params)
    {
        $newUserComment = clone $UserComment;
        $newUserComment->setAllData(array_merge($newUserComment->_data, $params));

        $this->dao->update($newUserComment);
    }

    public function delete(UserComment $UserComment)
    {
        $this->dao->delete($UserComment);
    }

    public function getCollector(): Collector
    {
        return app(Collector::class);
    }

    /**
     * Get an instance of the map class for mapping
     * announcements to their schema
     */
    public function getSchemaMap(): Schema
    {
        return app('maps')->withExtensions(Schema::class);
    }    
}
