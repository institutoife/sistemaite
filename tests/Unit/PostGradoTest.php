<?php

namespace Tests\Unit;

use App\Models\Grado;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class PostGradoTest extends TestCase
{
    public function test_grado_relaciona_estudiantes_mediante_el_pivote_existente(){
        $relation = (new Grado)->estudiantes();
        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertSame('estudiante_grado', $relation->getTable());
        $this->assertSame('grado_id', $relation->getForeignPivotKeyName());
        $this->assertSame('estudiante_id', $relation->getRelatedPivotKeyName());
    }
}
