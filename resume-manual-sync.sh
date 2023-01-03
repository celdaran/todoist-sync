DATE=$1

echo "Using date: $DATE"

echo "Running sync script..."
TODOIST_TTL=600 php -f todoist-sync.php >> logs/todoist-sync."$DATE".log

echo "Checking for any errors"
grep -i 'error\|warn' logs/todoist-sync."$DATE".log

echo "Confirm completion"
tail -3 logs/todoist-sync."$DATE".log

