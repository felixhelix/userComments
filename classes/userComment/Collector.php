<?php

namespace APP\plugins\generic\userComments\classes\userComment;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use PKP\core\interfaces\CollectorInterface;

class Collector implements CollectorInterface
{
    public DAO $dao;
    public ?array $contextIds = null;
    public ?int $publicationId = null;
    public ?bool $flagged = null;
    public ?int $count = null;
    public ?int $offset = null;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function getCount(): int
    {
        return $this->dao->getCount($this);
    }

    public function getIds(): Collection
    {
        return $this->dao->getIds($this);
    }

    public function getMany(): LazyCollection
    {
        return $this->dao->getMany($this);
    }

    public function filterByContextIds(?array $contextIds): self
    {
        $this->contextIds = $contextIds;
        return $this;
    }

    public function filterByPublicationId(?int $publicationId): self
    {
        $this->publicationId = $publicationId;
        return $this;
    }    

    public function filterByFlag(?bool $flagged): self
    {
        $this->flagged = $flagged;
        return $this;
    }     

    public function limit(?int $count): self
    {
        $this->count = $count;
        return $this;
    }

    public function offset(?int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function getQueryBuilder(): Builder
    {
        $uc = DB::table($this->dao->table . ' as uc')
            ->leftjoin($this->dao->settingsTable . ' as ucs', 'uc.' . $this->dao->primaryKeyColumn, '=', 'ucs.' . $this->dao->primaryKeyColumn)
            ->select(['uc.*', 'ucs.*'])
            ->where('ucs.setting_name',  'commentText');

        if (isset($this->contextIds)) {
            $uc->whereIn('uc.context_id', $this->contextIds);
        }

        if (isset($this->publicationId)) {
            $uc->where('uc.publication_id', $this->publicationId);
        }

        if (isset($this->flagged)) {
            $uc->where('uc.flagged',  $this->flagged);
        }        

        if (isset($this->count)) {
            $uc->limit($this->count);
        }

        if (isset($this->offset)) {
            $uc->offset($this->offset);
        }

        return $uc;
    }
}
