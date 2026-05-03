/**
 * Status tracking untuk article ingestion
 */
export enum IngestionStatus {
  QUEUED = 'queued',
  FETCHING = 'fetching',
  EXTRACTING = 'extracting',
  READY = 'ready',
  FAILED = 'failed'
}

/**
 * Error types untuk article ingestion
 */
export enum IngestionErrorType {
  INVALID_URL = 'INVALID_URL',
  NETWORK_ERROR = 'NETWORK_ERROR',
  CONTENT_TOO_LARGE = 'CONTENT_TOO_LARGE',
  EXTRACTION_FAILED = 'EXTRACTION_FAILED',
  SANITIZATION_FAILED = 'SANITIZATION_FAILED',
  SSRF_BLOCKED = 'SSRF_BLOCKED',
  TIMEOUT = 'TIMEOUT',
  UNKNOWN_ERROR = 'UNKNOWN_ERROR'
}

/**
 * Interface untuk hasil ekstraksi artikel
 */
export interface ArticleContent {
  title: string;
  content: string;
  excerpt: string;
  author?: string;
  publishedDate?: Date;
  wordCount: number;
  readingTime: number; // in minutes
  language?: string;
  tags: string[];
}

/**
 * Interface untuk metadata artikel
 */
export interface ArticleMetadata {
  url: string;
  canonicalUrl?: string;
  title: string;
  description?: string;
  author?: string;
  publishedDate?: Date;
  modifiedDate?: Date;
  language?: string;
  tags: string[];
  wordCount: number;
  readingTime: number;
  images: string[];
}

/**
 * Interface untuk hasil ingestion
 */
export interface IngestionResult {
  id: string;
  url: string;
  status: IngestionStatus;
  metadata?: ArticleMetadata;
  content?: ArticleContent;
  error?: IngestionError;
  attempts: number;
  createdAt: Date;
  updatedAt: Date;
  completedAt?: Date;
}

/**
 * Interface untuk error handling
 */
export interface IngestionError {
  type: IngestionErrorType;
  message: string;
  details?: any;
  retryable: boolean;
  timestamp: Date;
}

/**
 * Configuration untuk ArticleIngestionService
 */
export interface IngestionConfig {
  maxContentSize: number; // in bytes
  maxRedirects: number;
  requestTimeout: number; // in milliseconds
  maxRetries: number;
  retryDelay: number; // in milliseconds
  userAgent: string;
  allowedSchemes: string[];
  blockedHosts: string[];
  privateIpRanges: string[];
  sanitizeHtml: boolean;
  extractMetadata: boolean;
}

/**
 * Interface untuk URL validator
 */
export interface UrlValidationResult {
  isValid: boolean;
  normalizedUrl: string;
  canonicalUrl?: string;
  error?: string;
}

/**
 * Interface untuk HTTP response dengan SSRF protection
 */
export interface SafeHttpResponse {
  url: string;
  statusCode: number;
  headers: Record<string, string>;
  content: string;
  contentType: string;
  contentLength: number;
  redirected: boolean;
  redirectUrls: string[];
}