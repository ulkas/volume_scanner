<?php
/*
$result[$i]['coin'] = explode("\\", $pair)[0];
$result[$i]['pings'] = 0;
$result[$i]['Volume BTC'] = $recent_volume - $oldvolume;
$result[$i]['Volume BTC %'] = ($oldvolume == 0) ? 'N/A' : round((($recent_volume - $oldvolume) / $oldvolume) * 100, 2);
$result[$i]['Total Volume BTC'] = $recent_volume;
$result[$i]['Recent Volume BTC'] = $recent_volume - $recent_oldvolume;
$result[$i]['Recent Volume BTC %'] = ($recent_oldvolume == 0) ? 'N/A' : round((($recent_volume - $recent_oldvolume) / $recent_oldvolume ) *100, 2);
$result[$i]['trend'] = date('H:i:s m/d/y');
$result[$i++]['Datetime'] = date('H:i:s m/d/y');
*/

// https://www.coinigy.com/main/markets/LIQU/GNO/ETH
// https://www.coinigy.com/main/markets/BTRX/ADT/BTC
$coinigy_link = include('config/coinigy_exchanges.php');
$coinigy_link = 'https://www.coinigy.com/main/markets/' . $coinigy_link[$exchange] . '/';
 ?>
 <head>
   <meta http-equiv="refresh" content="3">
<style>
table,a {
    color: #333; /* Lighten up font color */
    font-family: Helvetica, Arial, sans-serif; /* Nicer font */
    width: 800px;
    border-collapse: collapse;
    border-spacing: 0;

}

td, th { border: 1px solid #CCC; white-space: nowrap;} /* Make cells a bit taller */

th {
    white-space: nowrap;
    padding-left: 5px;
    padding-right: 5px;
    font-weight: 500;
    background: #F3F3F3; /* Light grey background */
    font-weight: bold; /* Make sure they're bold */
}

td {
    text-align: center; /* Center our text */
}
p, div.inline {
  float:left;
  border: 1px;
  border-style: solid;
}
div.trendline {
  min-width: 400px;
}
.green {
  background-color: green;
}
.yellow {
  background-color: yellow;
}
.red {
  background-color: red;
}

body {
  background-color: grey;
}
</style>
</head>
<html>
  <body>
    <div>
      <table>
        <tbody>
        <tr>
          <th>Coin</th>
          <th>Pings</th>
          <th>Volume BTC</th>
          <th>Volume BTC %</th>
          <th>Total BTC now</th>
          <th>Recent Volume BTC</th>
          <th>Recent Volume BTC %</th>
          <th>Trendline %</th>
          <th>Datetime</th>
        </tr>
        <?php
foreach ($result as $key => $value) {
  echo "<tr>";
  foreach ($value as $key => $value2) {
    echo "<td>";
    if ($key == 'coin') {
      echo "<a href='$coinigy_link" . $value2 . "'>$value2</a>";
    } else if ($key == 'trend') {
      echo '<div class="trendline">';
      foreach ($value2 as $v) {
        if ($v > 0) $class = 'green';
        if ($v == 0) $class = 'yellow';
        if ($v < 0) $class = 'red';
        echo '<div class="inline ' . $class . '">' . $v . '</div>';
      }
      echo '</div>';
    } else {
      echo $value2;
    }
    echo "</td>";
  }
  echo "</tr>";
}

        ?>
      </tbody>
      </table>
    </div>
    <div>basic buy volume scanner, this scanner checks for bounces. watch for recent volume % increses and the trendline.</div>
    <div>base volume data is compared against <?php echo HISTORY_MINUTES; ?> minutes in history. total volume now is current total volume.
      recent volume data is the diff in the 2 most recent checks. ping is the number of the last positive checks.
       the trendline is the historic progress over time between checks (the greener to the end the better)</div>
    <!-- <div>donations welcome at <div>ETH:</div><div>LTC:</div><div>PPC:</div><div>BTC:</div><div>XPM:</div></div> -->
  </body>
</html>
