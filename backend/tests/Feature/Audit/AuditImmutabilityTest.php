<?php

namespace Tests\Feature\Audit;

use App\Services\Audit\AuditRecorder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class AuditImmutabilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_model_updates_are_rejected(): void
    {
        $event = app(AuditRecorder::class)->record('test.audit.created');
        $event->event = 'test.audit.changed';

        $this->expectException(LogicException::class);

        $event->save();
    }

    public function test_database_updates_are_rejected(): void
    {
        $event = app(AuditRecorder::class)->record('test.audit.created');

        $this->expectException(QueryException::class);

        DB::table('audit_events')
            ->where('id', $event->id)
            ->update(['event' => 'test.audit.changed']);
    }

    public function test_database_deletes_are_rejected(): void
    {
        $event = app(AuditRecorder::class)->record('test.audit.created');

        $this->expectException(QueryException::class);

        DB::table('audit_events')
            ->where('id', $event->id)
            ->delete();
    }
}
