<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GeminiSummarizationService;
use App\Services\LocalSummarizationService;
use App\Services\QuotaManagementService;
use App\Services\SummarizationService;
use App\Services\AdvancedPdfExportService;
use App\Services\ArticleExtractionService;
use App\Services\JobMonitoringService;
use App\Services\JobRetryService;
use App\Services\BackgroundProcessingService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Gemini Summarization Service
        $this->app->singleton(GeminiSummarizationService::class, function ($app) {
            return new GeminiSummarizationService();
        });

        // Register Local Summarization Service
        $this->app->singleton(LocalSummarizationService::class, function ($app) {
            return new LocalSummarizationService();
        });

        // Register Quota Management Service
        $this->app->singleton(QuotaManagementService::class, function ($app) {
            return new QuotaManagementService();
        });

        // Register Main Summarization Service
        $this->app->singleton(SummarizationService::class, function ($app) {
            return new SummarizationService(
                $app->make(GeminiSummarizationService::class),
                $app->make(LocalSummarizationService::class),
                $app->make(QuotaManagementService::class)
            );
        });

        // Register Article Extraction Service
        $this->app->singleton(ArticleExtractionService::class, function ($app) {
            return new ArticleExtractionService();
        });

        // Register Advanced PDF Export Service
        $this->app->singleton(AdvancedPdfExportService::class, function ($app) {
            return new AdvancedPdfExportService();
        });

        // Register Job Monitoring Service
        $this->app->singleton(JobMonitoringService::class, function ($app) {
            return new JobMonitoringService();
        });

        // Register Job Retry Service
        $this->app->singleton(JobRetryService::class, function ($app) {
            return new JobRetryService();
        });

        // Register Background Processing Service
        $this->app->singleton(BackgroundProcessingService::class, function ($app) {
            return new BackgroundProcessingService(
                $app->make(JobMonitoringService::class),
                $app->make(JobRetryService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
