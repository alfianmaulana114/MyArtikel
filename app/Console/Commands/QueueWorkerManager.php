<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Exception;

class QueueWorkerManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:worker-manager 
                            {action=start : Action to perform (start|stop|restart|status)}
                            {--queue=default : Queue to manage}
                            {--workers=1 : Number of workers to start}
                            {--timeout=300 : Worker timeout in seconds}
                            {--sleep=3 : Sleep time when no jobs available}
                            {--tries=3 : Number of tries for failed jobs}
                            {--memory=128 : Memory limit in MB}
                            {--daemon : Run as daemon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage queue workers with advanced configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $queue = $this->option('queue');
        $workers = (int) $this->option('workers');
        
        $this->info("Queue Worker Manager - Action: {$action}, Queue: {$queue}, Workers: {$workers}");
        
        switch ($action) {
            case 'start':
                $this->startWorkers($queue, $workers);
                break;
                
            case 'stop':
                $this->stopWorkers($queue);
                break;
                
            case 'restart':
                $this->restartWorkers($queue, $workers);
                break;
                
            case 'status':
                $this->showStatus($queue);
                break;
                
            default:
                $this->error("Invalid action: {$action}. Use: start, stop, restart, or status");
                return 1;
        }
        
        return 0;
    }

    /**
     * Start queue workers
     */
    private function startWorkers(string $queue, int $workers): void
    {
        $this->info("Starting {$workers} workers for queue: {$queue}");
        
        for ($i = 1; $i <= $workers; $i++) {
            $this->startWorker($queue, $i);
        }
        
        $this->info("All workers started successfully!");
    }

    /**
     * Start a single worker
     */
    private function startWorker(string $queue, int $workerId): void
    {
        try {
            $options = [
                'queue' => $queue,
                'timeout' => $this->option('timeout'),
                'sleep' => $this->option('sleep'),
                'tries' => $this->option('tries'),
                'memory' => $this->option('memory'),
            ];
            
            if ($this->option('daemon')) {
                $options['daemon'] = true;
            }
            
            $command = $this->buildWorkerCommand($options);
            
            $this->info("Starting worker {$workerId}: {$command}");
            
            // For Windows, use start command
            if (PHP_OS_FAMILY === 'Windows') {
                $startCommand = "start \"Queue Worker {$workerId}\" {$command}";
                pclose(popen($startCommand, 'r'));
            } else {
                // For Unix-like systems, use nohup
                $nohupCommand = "nohup {$command} > storage/logs/queue-worker-{$queue}-{$workerId}.log 2>&1 &";
                exec($nohupCommand);
            }
            
            // Give worker time to start
            sleep(1);
            
            Log::info("Queue worker started", [
                'worker_id' => $workerId,
                'queue' => $queue,
                'options' => $options
            ]);
            
        } catch (Exception $e) {
            $this->error("Failed to start worker {$workerId}: " . $e->getMessage());
            Log::error("Failed to start queue worker", [
                'worker_id' => $workerId,
                'queue' => $queue,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Build worker command
     */
    private function buildWorkerCommand(array $options): string
    {
        $command = 'php artisan queue:work';
        
        if ($options['queue'] !== 'default') {
            $command .= ' --queue=' . $options['queue'];
        }
        
        if ($options['timeout'] !== 60) {
            $command .= ' --timeout=' . $options['timeout'];
        }
        
        if ($options['sleep'] !== 3) {
            $command .= ' --sleep=' . $options['sleep'];
        }
        
        if ($options['tries'] !== 3) {
            $command .= ' --tries=' . $options['tries'];
        }
        
        if ($options['memory'] !== 128) {
            $command .= ' --memory=' . $options['memory'];
        }
        
        if (isset($options['daemon']) && $options['daemon']) {
            $command .= ' --daemon';
        }
        
        return $command;
    }

    /**
     * Stop queue workers
     */
    private function stopWorkers(string $queue): void
    {
        $this->info("Stopping workers for queue: {$queue}");
        
        try {
            // Send restart signal to all workers
            Artisan::call('queue:restart');
            
            // For more specific stopping, we would need to track PIDs
            // This is a simplified implementation
            
            $this->info("Stop signal sent to all workers");
            
            Log::info("Queue workers stop signal sent", [
                'queue' => $queue
            ]);
            
        } catch (Exception $e) {
            $this->error("Failed to stop workers: " . $e->getMessage());
            Log::error("Failed to stop queue workers", [
                'queue' => $queue,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Restart queue workers
     */
    private function restartWorkers(string $queue, int $workers): void
    {
        $this->info("Restarting workers for queue: {$queue}");
        
        // Stop existing workers
        $this->stopWorkers($queue);
        
        // Wait for workers to stop
        sleep(3);
        
        // Start new workers
        $this->startWorkers($queue, $workers);
        
        $this->info("Workers restarted successfully!");
    }

    /**
     * Show worker status
     */
    private function showStatus(string $queue): void
    {
        $this->info("Queue Status: {$queue}");
        
        // Check if queue has pending jobs
        $pendingJobs = \DB::table('jobs')
            ->where('queue', $queue)
            ->count();
            
        $this->info("Pending jobs: {$pendingJobs}");
        
        // Check failed jobs
        $failedJobs = \DB::table('failed_jobs')
            ->where('failed_at', '>', now()->subDay())
            ->count();
            
        $this->info("Failed jobs (last 24h): {$failedJobs}");
        
        // Show recent job processing times
        $recentJobs = \DB::table('job_batches')
            ->where('created_at', '>', now()->subHour())
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, finished_at)) as avg_time')
            ->first();
            
        if ($recentJobs && $recentJobs->avg_time) {
            $avgTime = round($recentJobs->avg_time, 2);
            $this->info("Average processing time (last hour): {$avgTime} seconds");
        }
        
        // Show worker process information (simplified)
        $this->showWorkerProcesses();
    }

    /**
     * Show worker process information
     */
    private function showWorkerProcesses(): void
    {
        $this->info("\nWorker Processes:");
        
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows tasklist
            exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV', $output);
            $processes = array_filter($output, function($line) {
                return str_contains($line, 'artisan queue:work');
            });
            
            if (empty($processes)) {
                $this->warn("No active queue workers found");
            } else {
                $this->info("Found " . count($processes) . " worker processes");
            }
        } else {
            // Unix-like systems - check for queue worker processes
            exec('ps aux | grep "artisan queue:work" | grep -v grep', $output);
            
            if (empty($output)) {
                $this->warn("No active queue workers found");
            } else {
                $this->info("Found " . count($output) . " worker processes");
                foreach ($output as $process) {
                    $this->line($process);
                }
            }
        }
    }
}