<?php
namespace App\Support;

use App\Services\SedeAccess;
use Illuminate\Database\Query\Builder;

class SedeQueryBuilder extends Builder
{
    private $guardWheres,$guardJoins,$guardFrom,$guardContext;
    public static function wrap(Builder $query): self
    {
        if ($query instanceof self) { return $query; }
        $scoped=new static($query->getConnection(),$query->getGrammar(),$query->getProcessor());
        foreach (get_object_vars($query) as $name=>$value) { $scoped->$name=$value; }
        return $scoped;
    }
    public function rememberSedeGuard(): void
    {
        $this->guardWheres=$this->wheres;$this->guardJoins=$this->joins;$this->guardFrom=$this->from;
        $this->guardContext=app(SedeAccess::class)->contextKey();
    }
    public function applySedeGuard(): void
    {
        $scope=app(SedeAccess::class);
        if ($this->guardContext===$scope->contextKey()&&$this->guardWheres===$this->wheres&&$this->guardJoins===$this->joins&&$this->guardFrom===$this->from) { return; }
        $scope->constrain($this);$this->rememberSedeGuard();
    }
    public function applyBeforeQueryCallbacks()
    {
        parent::applyBeforeQueryCallbacks();$this->applySedeGuard();
    }
    public function addWhereExistsQuery(Builder $query,$boolean='and',$not=false)
    {
        $query=static::wrap($query);$query->applySedeGuard();
        return parent::addWhereExistsQuery($query,$boolean,$not);
    }
}
