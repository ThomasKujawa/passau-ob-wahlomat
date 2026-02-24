<?php

// index.php
$questions  = require __DIR__ . '/questions.php';
$candidates = require __DIR__ . '/candidates.php';

function calculate_scores(array $questions, array $candidates, array $post): array
{
    $userAnswers = [];
    $weights     = [];

    foreach ($questions as $q) {
        $id = $q['id'];

        if (!isset($post['answer'][$id]) || $post['answer'][$id] === '') {
            continue; // übersprungen
        }

        $userAnswers[$id] = (int)$post['answer'][$id];
        $weights[$id]     = isset($post['weight'][$id]) ? 2 : 1; // 2 = "wichtig"
    }

    $results = [];

    foreach ($candidates as $key => $cand) {
        $score    = 0;
        $maxScore = 0;

        foreach ($userAnswers as $qid => $userVal) {
            $weight = $weights[$qid] ?? 1;

            if (!isset($cand['positions'][$qid])) {
                continue; // keine Position -> nicht gewertet
            }

            $candVal = (int)$cand['positions'][$qid];
            $diff    = abs($userVal - $candVal);

            if ($diff === 0) {
                $points = 2 * $weight; // volle Übereinstimmung
            } elseif ($diff === 1) {
                $points = 1 * $weight; // teilweise Übereinstimmung
            } else {
                $points = 0;           // gegensätzlich
            }

            $score    += $points;
            $maxScore += 2 * $weight;
        }

        $percent = $maxScore > 0 ? round($score / $maxScore * 100) : 0;

        $results[$key] = [
            'name'     => $cand['name'],
            'score'    => $score,
            'maxScore' => $maxScore,
            'percent'  => $percent,
        ];
    }

    uasort($results, function ($a, $b) {
        return $b['percent'] <=> $a['percent'];
    });

    return $results;
}

$isPost  = ($_SERVER['REQUEST_METHOD'] === 'POST');
$results = $isPost ? calculate_scores($questions, $candidates, $_POST) : null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Passau Wahl-O-Mat (privat, inoffiziell)</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">

    <header class="header">
        <h1>Passau Wahl-O-Mat <span class="badge">privates Projekt</span></h1>

        <div class="disclaimer">
            <p><strong>Hinweis / Disclaimer:</strong></p>
            <ul>
                <li>Dies ist ein <strong>privates, inoffizielles Informationsangebot</strong> einer Einzelperson und steht in <strong>keiner Verbindung zu Parteien, Kandidierenden oder offiziellen Stellen</strong>.</li>
                <li>Die Positionen der Kandidierenden zu den Thesen beruhen auf einer <strong>eigenen, nicht autorisierten Einschätzung</strong> anhand öffentlich zugänglicher Informationen. Sie können unvollständig oder fehlerhaft sein.</li>
                <li>Das Tool soll eine <strong>Orientierungshilfe</strong> bieten, ersetzt aber nicht die eigene Beschäftigung mit Programmen, Auftritten und Aussagen der Kandidierenden.</li>
                <li>Es erfolgt <strong>keine Speicherung deiner Antworten</strong>, es werden <strong>keine Analyse- oder Tracking-Dienste</strong> eingesetzt, und es werden <strong>keine personenbezogenen Profile</strong> erstellt.</li>
            </ul>
            <p class="disclaimer-note">
                Wenn du Kandidat:in bist und eine Korrektur oder Ergänzung deiner Positionen wünschst,
                kannst du den Betreiber über die im Impressum genannte Kontaktadresse erreichen.
            </p>
        </div>
    </header>

    <?php if (!$isPost): ?>
        <form method="post" action="" class="questionnaire">
            <?php
            $currentTopic = null;
            foreach ($questions as $q):
                if ($q['topic'] !== $currentTopic):
                    $currentTopic = $q['topic'];
                    ?>
                    <h2 class="topic-heading"><?= htmlspecialchars($currentTopic) ?></h2>
                <?php endif; ?>
                <section class="question">
                    <p class="question-text">
                        <strong><?= htmlspecialchars($q['id']) ?>:</strong>
                        <?= htmlspecialchars($q['text']) ?>
                    </p>
                    <div class="answers">
                        <label class="answer-option">
                            <input type="radio" name="answer[<?= htmlspecialchars($q['id']) ?>]" value="1">
                            <span>Stimme zu</span>
                        </label>
                        <label class="answer-option">
                            <input type="radio" name="answer[<?= htmlspecialchars($q['id']) ?>]" value="0">
                            <span>Neutral / teils-teils</span>
                        </label>
                        <label class="answer-option">
                            <input type="radio" name="answer[<?= htmlspecialchars($q['id']) ?>]" value="-1">
                            <span>Stimme nicht zu</span>
                        </label>
                        <label class="answer-option skip-option">
                            <input type="radio" name="answer[<?= htmlspecialchars($q['id']) ?>]" value="" checked>
                            <span>Überspringen</span>
                        </label>
                    </div>
                    <label class="important">
                        <input type="checkbox" name="weight[<?= htmlspecialchars($q['id']) ?>]" value="1">
                        Diese Aussage ist mir besonders wichtig
                    </label>
                </section>
            <?php endforeach; ?>

            <button type="submit" class="submit-btn">Auswerten</button>
        </form>
    <?php else: ?>
        <section class="results">
            <h2>Dein Ergebnis</h2>
            <p>
                Die folgenden Übereinstimmungswerte sind eine <strong>rein technische Auswertung</strong> deiner Antworten
                gegenüber den geschätzten Positionen der Kandidierenden. Sie stellen <strong>keine Wahlempfehlung</strong> dar.
            </p>

            <div class="results-table-wrapper">
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
                                <span class="percent-label"><?= (int)$res['percent'] ?> %</span>
                            </td>
                            <td><?= (int)$res['score'] ?> / <?= (int)$res['maxScore'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="results-note">
                Tipp: Sieh dir zusätzlich die Programme, Interviews und Podiumsdiskussionen der Kandidierenden an,
                um dir ein umfassendes Bild zu machen.
            </p>

            <p><a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="back-link">Zurück zum Fragebogen</a></p>
        </section>
    <?php endif; ?>

    <footer class="footer">
        <p>
            Dieses Projekt ist privat und inoffiziell. Keine Gewähr für Richtigkeit, Vollständigkeit und Aktualität. Angaben zum Impressum sind unter <a href="https://github.com/ThomasKujawa/passau-ob-wahlomat/" target="_blank">https://github.com/</a> nachzulesen.
        </p>
        <?php
            $version = file_exists(__DIR__ . '/version.txt') 
                ? 'v' . trim(file_get_contents(__DIR__ . '/version.txt')) 
                : 'dev';
            ?>
            <p class="version" style="font-size: 0.85em; color: #666; margin-top: 10px;">
                Version: <?= htmlspecialchars($version) ?>
            </p>
        <button onclick="downloadPDF()">📄 Ergebnis als PDF herunterladen</button>
    </footer>
</div>
<script>
// Fragen als JavaScript-Array verfügbar machen
const questions = <?php echo json_encode($questions); ?>;

function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Titel
    doc.setFontSize(22);
    doc.text("Meine Wahlempfehlung", 20, 25);
    doc.text("Passau OB-Wahl 2026", 20, 38);
    
    // Datum
    doc.setFontSize(10);
    doc.text("Erstellt: " + new Date().toLocaleDateString('de-DE'), 20, 50);
    
    // ❌ ECHTE ANTWORTEN aus Formular + Fragen-Array
    const answers = [];
    const answerInputs = document.querySelectorAll('input[type="radio"]:checked, select[name^="antwort"]');
    
    answerInputs.forEach(input => {
        const questionId = input.name.replace('antwort_', '').replace('antwort', '');
        const question = questions.find(q => q.id === questionId);
        
        if (question) {
            answers.push({
                id: question.id,
                topic: question.topic,
                text: question.text.substring(0, 80) + '...',
                answer: input.value // "ja", "nein", "neutral"
            });
        }
    });
    
    // Antworten schreiben
    let yPos = 75;
    doc.setFontSize(16);
    doc.text(`Meine Antworten (${answers.length}/${questions.length} Fragen):`, 20, yPos);
    yPos += 15;
    
    answers.forEach((answer, index) => {
        // Thema (kursiv)
        doc.setFont(undefined, 'italic');
        doc.setFontSize(10);
        doc.text(answer.topic, 20, yPos);
        doc.setFont(undefined, 'normal'); // Normal zurück
        
        // Frage + Antwort
        doc.setFontSize(11);
        doc.text(`${index + 1}. ${answer.text}`, 20, yPos + 6);
        doc.setFontSize(12);
        doc.text(`   → ${translateAnswer(answer.answer)}`, 20, yPos + 14);
        
        yPos += 28;
        
        // Neue Seite?
        if (yPos > 260) {
            doc.addPage();
            yPos = 25;
        }
    });
    
    // Empfehlung (Platzhalter - später echte Logik)
    doc.setFontSize(18);
    doc.text("🎯 Empfohlener Kandidat:", 20, yPos + 10);
    doc.setFontSize(20);
    doc.text("Noch nicht berechnet", 20, yPos + 28);
    
    // Fußzeile
    doc.setFontSize(9);
    doc.text("passau-ob.meinfamilienfreund.de", 20, 290);
    
    doc.save("wahlomat-ergebnis.pdf");
}

// Hilfsfunktion für deutsche Antworten
function translateAnswer(answer) {
    switch(answer) {
        case 'ja': return '✓ JA';
        case 'nein': return '✗ NEIN';
        case 'neutral': return '~ NEUTRAL';
        default: return answer;
    }
}
</script>
</body>
</html>
