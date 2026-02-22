<?php
// index.php
$questions = require __DIR__ . '/questions.php';
$candidates = require __DIR__ . '/candidates.php';

function calculate_scores(array $questions, array $candidates, array $post): array
{
    // Antworten des Nutzers: question_id => -1/0/1
    $userAnswers = [];
    $weights = [];

    foreach ($questions as $q) {
        $id = $q['id'];

        if (!isset($post['answer'][$id])) {
            continue; // übersprungen
        }

        $userAnswers[$id] = (int)$post['answer'][$id];

        // Gewichtung (1 normal, 2 „wichtig“)
        $weights[$id] = isset($post['weight'][$id]) ? 2 : 1;
    }

    $results = [];

    foreach ($candidates as $key => $cand) {
        $score = 0;
        $maxScore = 0;

        foreach ($userAnswers as $qid => $userVal) {
            $weight = $weights[$qid] ?? 1;

            if (!isset($cand['positions'][$qid])) {
                continue; // keine Position -> nicht gewertet
            }

            $candVal = (int)$cand['positions'][$qid];
            $diff = abs($userVal - $candVal);

            // Übereinstimmung nach deinem Modell
            if ($diff === 0) {
                $points = 2 * $weight;   // volle Übereinstimmung
            } elseif ($diff === 1) {
                $points = 1 * $weight;   // teilweise Übereinstimmung
            } else { // diff === 2
                $points = 0;             // Gegensätzlich
            }

            $score += $points;
            $maxScore += 2 * $weight;
        }

        // Prozentwert (wenn maxScore 0 -> keine angerechneten Fragen)
        $percent = $maxScore > 0 ? round($score / $maxScore * 100) : 0;

        $results[$key] = [
            'name' => $cand['name'],
            'score' => $score,
            'maxScore' => $maxScore,
            'percent' => $percent,
        ];
    }

    // Nach Prozent absteigend sortieren
    uasort($results, function ($a, $b) {
        return $b['percent'] <=> $a['percent'];
    });

    return $results;
}

$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$results = $isPost ? calculate_scores($questions, $candidates, $_POST) : null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Passau Wahl-O-Mat (privat, inoffiziell)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Passau Wahl-O-Mat (privates Projekt)</h1>
    <p>
        Dieses Tool speichert keine personenbezogenen Daten, verwendet keine Cookies
        (außer technisch notwendige Session-Cookies, falls später nötig) und bindet keine externen Tracker ein.
        Alle Berechnungen erfolgen nur in deinem Browser-Request und werden nicht dauerhaft gespeichert.
    </p>

    <?php if (!$isPost): ?>
        <form method="post" action="">
            <?php
            $currentTopic = null;
            foreach ($questions as $q):
                if ($q['topic'] !== $currentTopic):
                    $currentTopic = $q['topic'];
                    ?>
                    <h2><?= htmlspecialchars($currentTopic) ?></h2>
                <?php endif; ?>
                <div class="question">
                    <p><strong><?= htmlspecialchars($q['id']) ?>:</strong>
                        <?= htmlspecialchars($q['text']) ?></p>
                    <div class="answers">
                        <label>
                            <input type="radio" name="answer[<?= htmlspecialchars($q['id']) ?>]" value="1">
                            Stimme zu
                        </label>
                        <label>
                            <input type="radio" name="answer[<?= htmlspecialchars($q['id']) ?>]" value="0">
                            Neutral / teils-teils
                        </label>
                        <label>
                            <input type="radio" name="answer[<?= htmlspecialchars($q['id']) ?>]" value="-1">
                            Stimme nicht zu
                        </label>
                        <label class="skip">
                            <input type="radio" name="answer[<?= htmlspecialchars($q['id']) ?>]" value=""
                                   checked>
                            Überspringen
                        </label>
                    </div>
                    <label class="important">
                        <input type="checkbox" name="weight[<?= htmlspecialchars($q['id']) ?>]" value="1">
                        Diese Aussage ist mir besonders wichtig
                    </label>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="submit-btn">Auswerten</button>
        </form>
    <?php else: ?>
        <h2>Dein Ergebnis</h2>
        <p>
            Die folgenden Übereinstimmungswerte sind eine rein technische Auswertung deiner Antworten
            gegenüber den geschätzten Positionen der Kandidierenden.
        </p>
        <table class="results-table">
            <thead>
            <tr>
                <th>Kandidat:in</th>
                <th>Übereinstimmung</th>
                <th>Punktzahl</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($results as $res): ?>
                <tr>
                    <td><?= htmlspecialchars($res['name']) ?></td>
                    <td>
                        <div class="bar-wrapper">
                            <div class="bar" style="width: <?= (int)$res['percent'] ?>%;"></div>
                        </div>
                        <?= (int)$res['percent'] ?> %
                    </td>
                    <td><?= (int)$res['score'] ?> / <?= (int)$res['maxScore'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p><a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">Zurück zum Fragebogen</a></p>
    <?php endif; ?>
</div>
</body>
</html>
