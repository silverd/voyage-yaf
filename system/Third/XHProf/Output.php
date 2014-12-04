<?php

class Third_XHProf_Output
{
    public static function getRunId($namespace = 'silver')
    {
        // stop profiler
        $xhprofData = xhprof_disable();

        include_once __DIR__ . "/xhprof_lib/utils/xhprof_lib.php";
        include_once __DIR__ . "/xhprof_lib/utils/xhprof_runs.php";

        // save raw data for this profiler run using default
        // implementation of iXHProfRuns.
        $xhprofRuns = new XHProfRuns_Default();

        // save the run under a namespace "xhprof_foo"
        $runId = $xhprofRuns->save_run($xhprofData, $namespace);

        return $runId;
    }
}