#!/bin/bash

# Laravel Queue System Setup Script
# This script helps set up and manage the queue system for myartikel

echo "🚀 Laravel Queue System Setup for MyArtikel"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   print_error "This script should not be run as root"
   exit 1
fi

# Check PHP version
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
print_info "PHP Version: $PHP_VERSION"

if [[ $(echo "$PHP_VERSION < 8.0" | bc) -eq 1 ]]; then
    print_error "PHP 8.0 or higher is required"
    exit 1
fi

# Function to run Laravel commands
run_laravel_command() {
    print_info "Running: php artisan $1"
    php artisan $1
    if [ $? -eq 0 ]; then
        print_status "Command completed successfully"
    else
        print_error "Command failed: $1"
        return 1
    fi
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to install Supervisor (if not present)
install_supervisor() {
    if command_exists supervisorctl; then
        print_status "Supervisor is already installed"
        return 0
    fi

    print_info "Installing Supervisor..."
    
    if command_exists apt-get; then
        sudo apt-get update
        sudo apt-get install -y supervisor
    elif command_exists yum; then
        sudo yum install -y supervisor
    elif command_exists brew; then
        brew install supervisor
    else
        print_error "Could not install Supervisor. Please install it manually."
        return 1
    fi
    
    if [ $? -eq 0 ]; then
        print_status "Supervisor installed successfully"
        sudo systemctl enable supervisor
        sudo systemctl start supervisor
    else
        print_error "Failed to install Supervisor"
        return 1
    fi
}

# Function to setup queue tables
setup_queue_tables() {
    print_info "Setting up queue tables..."
    
    run_laravel_command "queue:table"
    run_laravel_command "queue:batches-table"
    run_laravel_command "queue:failed-table"
    
    # Run migrations
    run_laravel_command "migrate"
    
    print_status "Queue tables setup completed"
}

# Function to create supervisor configuration
create_supervisor_config() {
    print_info "Creating Supervisor configuration..."
    
    # Get current directory
    CURRENT_DIR=$(pwd)
    PROJECT_NAME=$(basename "$CURRENT_DIR")
    
    # Create supervisor config directory if it doesn't exist
    SUPERVISOR_CONF_DIR="/etc/supervisor/conf.d"
    if [ ! -d "$SUPERVISOR_CONF_DIR" ]; then
        sudo mkdir -p "$SUPERVISOR_CONF_DIR"
    fi
    
    # Copy our supervisor config
    sudo cp supervisor-queue.conf "$SUPERVISOR_CONF_DIR/${PROJECT_NAME}-queue.conf"
    
    # Replace placeholder paths with actual paths
    sudo sed -i "s|/path/to/your/project|$CURRENT_DIR|g" "$SUPERVISOR_CONF_DIR/${PROJECT_NAME}-queue.conf"
    
    # Update the log file paths to use the correct storage directory
    sudo sed -i "s|storage/logs/|$CURRENT_DIR/storage/logs/|g" "$SUPERVISOR_CONF_DIR/${PROJECT_NAME}-queue.conf"
    
    print_status "Supervisor configuration created"
}

# Function to setup cron jobs
setup_cron_jobs() {
    print_info "Setting up cron jobs..."
    
    # Create log directory for cron jobs
    mkdir -p storage/logs/cron
    
    # Add cron jobs
    (crontab -l 2>/dev/null; echo "# MyArtikel Queue Management") | crontab -
    (crontab -l 2>/dev/null; echo "* * * * * cd $(pwd) && php artisan schedule:run >> storage/logs/cron/schedule.log 2>&1") | crontab -
    (crontab -l 2>/dev/null; echo "0 2 * * * cd $(pwd) && php artisan queue:restart >> storage/logs/cron/queue-restart.log 2>&1") | crontab -
    (crontab -l 2>/dev/null; echo "0 3 * * * cd $(pwd) && php artisan queue:flush >> storage/logs/cron/queue-flush.log 2>&1") | crontab -
    
    print_status "Cron jobs configured"
}

# Function to create log directories
create_log_directories() {
    print_info "Creating log directories..."
    
    mkdir -p storage/logs/queue
    mkdir -p storage/logs/cron
    mkdir -p storage/logs/jobs
    
    print_status "Log directories created"
}

# Function to test queue functionality
test_queue_functionality() {
    print_info "Testing queue functionality..."
    
    # Test basic queue command
    php artisan queue:work --once --queue=default
    if [ $? -eq 0 ]; then
        print_status "Queue worker test passed"
    else
        print_error "Queue worker test failed"
        return 1
    fi
    
    # Test job dispatching
    php artisan tinker --execute="
    \$job = new \App\Jobs\ProcessSummaryGeneration(1, 1, 1, ['test' => true]);
    dispatch(\$job);
    echo 'Test job dispatched successfully';
    "
    
    if [ $? -eq 0 ]; then
        print_status "Job dispatch test passed"
    else
        print_error "Job dispatch test failed"
        return 1
    fi
}

# Function to create systemd service (alternative to supervisor)
create_systemd_service() {
    print_info "Creating systemd service..."
    
    CURRENT_DIR=$(pwd)
    PROJECT_NAME=$(basename "$CURRENT_DIR")
    SERVICE_NAME="${PROJECT_NAME}-queue"
    
    sudo tee /etc/systemd/system/${SERVICE_NAME}.service > /dev/null <<EOF
[Unit]
Description=MyArtikel Queue Worker
After=network.target

[Service]
Type=simple
User=$USER
WorkingDirectory=$CURRENT_DIR
ExecStart=/usr/bin/php artisan queue:work --queue=high-priority,default,low-priority --sleep=3 --tries=3 --timeout=90
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

    sudo systemctl daemon-reload
    sudo systemctl enable ${SERVICE_NAME}
    
    print_status "Systemd service created: ${SERVICE_NAME}"
}

# Function to start services
start_services() {
    print_info "Starting queue services..."
    
    # Option 1: Using Supervisor (recommended)
    if command_exists supervisorctl; then
        sudo supervisorctl reread
        sudo supervisorctl update
        sudo supervisorctl start all
        print_status "Supervisor services started"
    fi
    
    # Option 2: Using systemd
    CURRENT_DIR=$(pwd)
    PROJECT_NAME=$(basename "$CURRENT_DIR")
    SERVICE_NAME="${PROJECT_NAME}-queue"
    
    if systemctl list-unit-files | grep -q "${SERVICE_NAME}.service"; then
        sudo systemctl start ${SERVICE_NAME}
        print_status "Systemd service started: ${SERVICE_NAME}"
    fi
    
    # Start queue workers manually as fallback
    print_info "Starting queue workers manually..."
    nohup php artisan queue:work --queue=high-priority,default --sleep=3 --tries=3 --timeout=90 > storage/logs/queue/default.log 2>&1 &
    nohup php artisan queue:work --queue=summarization --sleep=2 --tries=3 --timeout=600 --memory=256 > storage/logs/queue/summarization.log 2>&1 &
    nohup php artisan queue:work --queue=exports --sleep=3 --tries=2 --timeout=300 --memory=512 > storage/logs/queue/exports.log 2>&1 &
    nohup php artisan queue:work --queue=low-priority --sleep=5 --tries=5 --timeout=300 --memory=256 > storage/logs/queue/low-priority.log 2>&1 &
    
    print_status "Queue workers started manually"
}

# Function to show status
show_status() {
    print_info "Queue System Status"
    echo "==================="
    
    # Check if workers are running
    if pgrep -f "artisan queue:work" > /dev/null; then
        print_status "Queue workers are running"
        pgrep -f "artisan queue:work" | wc -l | xargs echo "Active workers:"
    else
        print_warning "No queue workers are currently running"
    fi
    
    # Check pending jobs
    PENDING_JOBS=$(php artisan queue:status 2>/dev/null | grep "pending" | awk '{print $2}' || echo "0")
    echo "Pending jobs: $PENDING_JOBS"
    
    # Check failed jobs
    FAILED_JOBS=$(php artisan queue:failed --json 2>/dev/null | jq '. | length' || echo "0")
    echo "Failed jobs: $FAILED_JOBS"
    
    # Check supervisor status
    if command_exists supervisorctl; then
        echo ""
        print_info "Supervisor Status:"
        sudo supervisorctl status | grep queue || echo "No queue processes found"
    fi
    
    # Check systemd status
    CURRENT_DIR=$(pwd)
    PROJECT_NAME=$(basename "$CURRENT_DIR")
    SERVICE_NAME="${PROJECT_NAME}-queue"
    
    if systemctl is-active --quiet ${SERVICE_NAME} 2>/dev/null; then
        echo ""
        print_status "Systemd service is active: ${SERVICE_NAME}"
    fi
}

# Function to stop services
stop_services() {
    print_info "Stopping queue services..."
    
    # Kill queue workers
    pkill -f "artisan queue:work" || true
    
    # Stop supervisor services
    if command_exists supervisorctl; then
        sudo supervisorctl stop all || true
    fi
    
    # Stop systemd service
    CURRENT_DIR=$(pwd)
    PROJECT_NAME=$(basename "$CURRENT_DIR")
    SERVICE_NAME="${PROJECT_NAME}-queue"
    
    if systemctl is-active --quiet ${SERVICE_NAME} 2>/dev/null; then
        sudo systemctl stop ${SERVICE_NAME}
    fi
    
    print_status "Queue services stopped"
}

# Function to restart services
restart_services() {
    print_info "Restarting queue services..."
    stop_services
    sleep 3
    start_services
    print_status "Queue services restarted"
}

# Function to cleanup old logs
cleanup_logs() {
    print_info "Cleaning up old logs..."
    
    # Remove logs older than 30 days
    find storage/logs -name "*.log" -type f -mtime +30 -delete 2>/dev/null || true
    find storage/logs/queue -name "*.log" -type f -mtime +7 -delete 2>/dev/null || true
    
    print_status "Old logs cleaned up"
}

# Function to show help
show_help() {
    echo "MyArtikel Queue System Setup"
    echo "Usage: $0 [OPTION]"
    echo ""
    echo "Options:"
    echo "  setup       Complete setup (install dependencies, configure, start services)"
    echo "  start       Start queue services"
    echo "  stop        Stop queue services"
    echo "  restart     Restart queue services"
    echo "  status      Show queue system status"
    echo "  test        Test queue functionality"
    echo "  cleanup     Cleanup old logs"
    echo "  help        Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 setup    # Complete setup"
    echo "  $0 start    # Start services"
    echo "  $0 status   # Check status"
}

# Main function
main() {
    case "${1:-help}" in
        setup)
            print_info "Starting complete queue system setup..."
            
            # Check if .env file exists
            if [ ! -f .env ]; then
                print_error ".env file not found. Please copy .env.example to .env and configure it."
                exit 1
            fi
            
            # Check if composer dependencies are installed
            if [ ! -d vendor ]; then
                print_info "Installing composer dependencies..."
                composer install --no-dev --optimize-autoloader
            fi
            
            install_supervisor
            setup_queue_tables
            create_log_directories
            create_supervisor_config
            setup_cron_jobs
            test_queue_functionality
            start_services
            show_status
            
            print_status "Queue system setup completed successfully!"
            print_info "You can now use the queue system for background processing."
            print_info "Monitor the system at: http://your-domain/jobs/monitoring"
            ;;
            
        start)
            start_services
            show_status
            ;;
            
        stop)
            stop_services
            ;;
            
        restart)
            restart_services
            show_status
            ;;
            
        status)
            show_status
            ;;
            
        test)
            test_queue_functionality
            ;;
            
        cleanup)
            cleanup_logs
            ;;
            
        help|--help|-h)
            show_help
            ;;
            
        *)
            print_error "Unknown option: $1"
            show_help
            exit 1
            ;;
    esac
}

# Run main function
main "$@"