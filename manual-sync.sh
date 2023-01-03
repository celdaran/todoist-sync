DATE=$(date -r data/todoist-sync.db +"%Y%m%d-%H%M%S")

echo "Last DB date: $DATE"

echo "Backing up database..."
cp data/todoist-sync.db data/todoist-sync."$DATE".db

echo "Running sync script..."
TODOIST_TTL=$1 php -f todoist-sync.php >> logs/todoist-sync."$DATE".log

echo "Checking for any errors"
grep -i 'error\|warn' logs/todoist-sync."$DATE".log

echo "Confirm completion"
tail -3 logs/todoist-sync."$DATE".log
