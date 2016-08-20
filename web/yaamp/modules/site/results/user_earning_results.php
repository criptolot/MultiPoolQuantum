<?php

function WriteBoxHeader($title)
{
	echo "<div class='main-left-box'>";
	echo "<div class='main-left-title'>$title</div>";
	echo "<div class='main-left-inner'>";
}

$algo = user()->getState('yaamp-algo');

$user = getuserparam(getparam('address'));
if(!$user || $user->is_locked) return;

$count = getparam('count');
$count = $count? $count: 50;

WriteBoxHeader("Last $count Earnings: $user->username");
$earnings = getdbolist('db_earnings', "userid=$user->id order by create_time desc limit :count", array(':count'=>$count));

echo <<<EOT

<style type="text/css">
span.block { padding: 2px; display: inline-block; text-align: center; min-width: 75px; border-radius: 3px; }
span.block.immature { color: white; background-color: #f0ad4e; }
span.block.exchange { color: white; background-color: #5cb85c; }
span.block.cleared  { color: white; background-color: gray; }
</style>

<table class="dataGrid2">
<thead>
<tr>
<td></td>
<th>Name</th>
<th align=right>Amount</th>
<th align=right>Percent</th>
<th align=right>mBTC</th>
<th align=right>Time</th>
<th align=right>Status</th>
</tr>
</thead>

EOT;

$showrental = (bool) YAAMP_RENTAL;

foreach($earnings as $earning)
{
	$coin = getdbo('db_coins', $earning->coinid);
	$block = getdbo('db_blocks', $earning->blockid);
	if (!$block) {
		debuglog("missing block id {$earning->blockid}!");
		continue;
	}

	$d = datetoa2($earning->create_time);
	if(!$coin)
	{
		if (!$showrental)
			continue;

		$reward = bitcoinvaluetoa($earning->amount);
		$value = altcoinvaluetoa($earning->amount*1000);
		$percent = $block? mbitcoinvaluetoa($earning->amount*100/$block->amount): '';
		$algo = $block? $block->algo: '';

		echo "<tr class='ssrow'>";
		echo "<td width=18><img width=16 src='/images/btc.png'></td>";
		echo "<td><b>Rental</b><span style='font-size: .8em'> ($algo)</span></td>";
		echo "<td align=right style='font-size: .8em'><b>$reward BTC</b></td>";
		echo "<td align=right style='font-size: .8em'>{$percent}%</td>";
		echo "<td align=right style='font-size: .8em'>$value</td>";
		echo "<td align=right style='font-size: .8em'>$d ago</td>";
		echo "<td align=right style='font-size: .8em'>Cleared</td>";
		echo "</tr>";

		continue;
	}

	$reward = altcoinvaluetoa($earning->amount);
	$percent = mbitcoinvaluetoa($earning->amount*100/$block->amount);
	$value = altcoinvaluetoa($earning->amount*$earning->price*1000);

	$blockUrl = $coin->createExplorerLink($coin->name, array('height'=>$block->height));
	echo "<tr class='ssrow'>";
	echo "<td width=18><img width=16 src='$coin->image'></td>";
	echo "<td><b>$blockUrl</b><span style='font-size: .8em'> ($coin->algo)</span></td>";
	echo "<td align=right style='font-size: .8em'><b>$reward $coin->symbol_show</b></td>";
	echo "<td align=right style='font-size: .8em'>{$percent}%</td>";
	echo "<td align=right style='font-size: .8em'>$value</td>";
	echo "<td align=right style='font-size: .8em'>$d ago</td>";
	echo "<td align=right style='font-size: .8em'>";

	if($earning->status == 0)
		echo '<span class="block immature">Immature ('.$block->confirmations.')</span>';

	else if($earning->status == 1)
		echo '<span class="block exchange">'.(YAAMP_ALLOW_EXCHANGE ? 'Exchange' : 'Confirmed').'</span>';

	else if($earning->status == 2)
		echo '<span class="block cleared">Cleared</span>';

	echo "</td>";
	echo "</tr>";
}

echo "</table>";

echo "<br></div></div><br>";




