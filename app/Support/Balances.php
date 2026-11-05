<?php

namespace App\Support;

/**
 * Pure maths (no database, no framework) so it is easy to test.
 *
 * net > 0  => the person should get money back
 * net < 0  => the person owes money
 */
class Balances
{
    /**
     * @param  int[]  $memberIds
     * @param  array<int,array{paid_by:int,amount:int,shares:array<int,int>}>  $expenses
     * @param  array<int,array{from:int,to:int,amount:int}>  $settlements
     * @return array{net:array<int,int>,transfers:array<int,array{from:int,to:int,amount:int}>}
     */
    public static function compute(array $memberIds, array $expenses, array $settlements): array
    {
        $net = array_fill_keys($memberIds, 0);

        foreach ($expenses as $e) {
            $net[$e['paid_by']] = ($net[$e['paid_by']] ?? 0) + $e['amount'];
            foreach ($e['shares'] as $memberId => $cents) {
                $net[$memberId] = ($net[$memberId] ?? 0) - $cents;
            }
        }

        // Paying someone back moves you towards zero from the owing side.
        foreach ($settlements as $s) {
            $net[$s['from']] = ($net[$s['from']] ?? 0) + $s['amount'];
            $net[$s['to']] = ($net[$s['to']] ?? 0) - $s['amount'];
        }

        $creditors = [];
        $debtors = [];
        foreach ($net as $id => $n) {
            if ($n > 0) {
                $creditors[] = ['id' => $id, 'amt' => $n];
            } elseif ($n < 0) {
                $debtors[] = ['id' => $id, 'amt' => -$n];
            }
        }

        // biggest first, ties by id so the output is stable
        $order = fn ($a, $b) => [$b['amt'], $a['id']] <=> [$a['amt'], $b['id']];
        usort($creditors, $order);
        usort($debtors, $order);

        $transfers = [];
        $i = 0;
        $j = 0;
        while ($i < count($creditors) && $j < count($debtors)) {
            $pay = min($creditors[$i]['amt'], $debtors[$j]['amt']);
            $transfers[] = ['from' => $debtors[$j]['id'], 'to' => $creditors[$i]['id'], 'amount' => $pay];
            $creditors[$i]['amt'] -= $pay;
            $debtors[$j]['amt'] -= $pay;
            if ($creditors[$i]['amt'] === 0) {
                $i++;
            }
            if ($debtors[$j]['amt'] === 0) {
                $j++;
            }
        }

        return ['net' => $net, 'transfers' => $transfers];
    }
}
