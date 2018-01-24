## install
uses redis as the database server
> sudo apt-get install redis-server

download dependencies (or install manually ccxt and credis). type in the folder with composer.json file
> composer install

setup crons (or run those 2 files directly manually - loadTickers.php once, parseOrderBooks.php as much as possible):
> `* */12 * * * php /var/www/html/scanner/loadTickers.php >> /var/log/apache2/scanner/loadTickers`

> `* * * * * php /var/www/html/scanner/parseOrderBooks.php >> /var/log/apache2/scanner/orderBook`

> `* * * * * ( sleep 30 ;  php /var/www/html/scanner/parseOrderBooks.php >> /var/log/apache2/scanner/orderBook)`

