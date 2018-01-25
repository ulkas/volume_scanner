## install
uses redis as the database server
> sudo apt-get install redis-server

download dependencies (or install manually ccxt and credis). type in the folder with composer.json file
> composer install

setup crons (or run those 2 files directly manually - loadTickers.php once, parseOrderBooks.php as much as possible):
```
`* */12 * * * php /var/www/html/scanner/loadTickers.php >> /var/log/apache2/scanner/loadTickers`

 `* * * * * php /var/www/html/scanner/parseOrderBooks.php >> /var/log/apache2/scanner/orderBook`

 `* * * * * ( sleep 30 ;  php /var/www/html/scanner/parseOrderBooks.php >> /var/log/apache2/scanner/orderBook)`
```

## how it works

### script loadTickers.php
loads exchange and coin info. you can define what type of coins to keep track (low volume coins etc), maximum coins (picks randomly) for your tests - tracking all coins will probably give you ban by the exchange due to overloading. all data expires after `$ttl` hours;
```
//define minimum volume treshold
$volume_min = 50;
$volume_max = 200;
//maximum coins to keep track of
$max_coins = 30;
//key expiration
$ttl = 60 * 60 * 24; //24hours
$exchanges =[
  'liqui',
  'bittrex',
  'binance',
  'hitbtc2',
  'therock',
];
```

### script parseOrderBooks.php

main volume checker. checks all coins picked by the loadTickers script for volume, and saves it into memory. default ttl 10 minutes. it also filtrates extreme values, so it only counts buy volume maximum 10x below the current price (cause lower values create high volumes with no investments)
```
//compare with how far into history
const HISTORY_MINUTES = 10;
```

### data format
redis is a key-value store. example of data storage:
>vagrant@vagrant:/var/www/html/scanner# redis-cli

```
127.0.0.1:6379> keys *bittre*MYST*
 1) "orderbook.bittrex.MYST/BTC.bid.recent.volume.old.10.7"
 2) "orderbook.bittrex.MYST/BTC.bid.recent.volume.old.10.1"
 3) "orderbook.bittrex.MYST/BTC.bid.recent.volume.old.10.0"
 4) "orderbook.bittrex.MYST/BTC.bid.recent.volume.old"
 5) "orderbook.bittrex.MYST/BTC.bid.recent.volume.old.10.4"
 6) "orderbook.bittrex.MYST/BTC.bid.recent.volume.old.10.8"
 7) "orderbook.bittrex.MYST/BTC.bid.recent.volume"
 8) "orderbook.bittrex.MYST/BTC.bid.recent.volume.old.10.9"
 9) "orderbook.bittrex.MYST/BTC.bid.recent.volume.old.10.3"
10) "orderbook.bittrex.MYST/BTC.bid.recent.volume.old.10.6"
11) "orderbook.bittrex.MYST/BTC.bid.recent.volume.old.10.2"
```

- each key contains the volume at the specific minute. note that the time here isnt chronological - if the current minute is 5, than the key `orderbook.bittrex.MYST/BTC.bid.recent.volume.old.10.6` is the oldest value (10minutes in the past) from all.
- this means that per each minute in the history, there is only the last record saved no matter how much the script parseOrderBooks.php is fired.
- there are also 2 very last values stored outside this minute scope in `"orderbook.bittrex.MYST/BTC.bid.recent.volume"` and `"orderbook.bittrex.MYST/BTC.bid.recent.volume.old"` . this is the most actuall volume diff (the recent column in the table) and the quickest indicator of a pump for daily trades (better called seconds trades)
- the scanner script just parses this volume data from memory and formats it to the output:
```
//filter extreme price differences
const PRICE_JUMP = 10;
```

