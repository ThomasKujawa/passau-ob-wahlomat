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
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
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

// Kandidaten als JavaScript-Array verfügbar machen
const candidates = <?php echo json_encode($candidates); ?>;

// ✅ PHP-Ergebnisse in JavaScript verfügbar machen (falls POST)
<?php if ($isPost): ?>
const phpResults = <?php echo json_encode(array_values($results)); ?>;
const phpUserAnswers = <?php 
    $userAnswersForJS = [];
    foreach ($questions as $q) {
        if (isset($_POST['answer'][$q['id']]) && $_POST['answer'][$q['id']] !== '') {
            $userAnswersForJS[$q['id']] = (int)$_POST['answer'][$q['id']];
        }
    }
    echo json_encode($userAnswersForJS); 
?>;
const phpWeights = <?php 
    $weightsForJS = [];
    foreach ($questions as $q) {
        if (isset($_POST['answer'][$q['id']]) && $_POST['answer'][$q['id']] !== '') {
            $weightsForJS[$q['id']] = isset($_POST['weight'][$q['id']]) ? 2 : 1;
        }
    }
    echo json_encode($weightsForJS); 
?>;
<?php else: ?>
const phpResults = null;
const phpUserAnswers = null;
const phpWeights = null;
<?php endif; ?>

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
    
    // ✅ Verwende PHP-Ergebnisse, falls verfügbar (POST), sonst aus Formular
    let userAnswers = {};
    let weights = {};
    let results = [];
    
    if (phpResults !== null && phpUserAnswers !== null) {
        // ✅ NACH POST: Verwende die berechneten PHP-Ergebnisse
        userAnswers = phpUserAnswers;
        weights = phpWeights;
        results = phpResults;
    } else {
        // VOR POST: Aus Formular lesen (sollte nicht vorkommen, aber als Fallback)
        questions.forEach(q => {
            const radioInput = document.querySelector(`input[name="answer[${q.id}]"]:checked`);
            const weightInput = document.querySelector(`input[name="weight[${q.id}]"]`);
            
            if (radioInput && radioInput.value !== '') {
                userAnswers[q.id] = parseInt(radioInput.value);
                weights[q.id] = (weightInput && weightInput.checked) ? 2 : 1;
            }
        });
        
        // Berechnung durchführen (Fallback)
        Object.keys(candidates).forEach(key => {
            const cand = candidates[key];
            let score = 0;
            let maxScore = 0;
            
            Object.keys(userAnswers).forEach(qid => {
                const userVal = userAnswers[qid];
                const weight = weights[qid] || 1;
                
                if (!cand.positions[qid]) {
                    return;
                }
                
                const candVal = parseInt(cand.positions[qid]);
                const diff = Math.abs(userVal - candVal);
                
                let points = 0;
                if (diff === 0) {
                    points = 2 * weight;
                } else if (diff === 1) {
                    points = 1 * weight;
                } else {
                    points = 0;
                }
                
                score += points;
                maxScore += 2 * weight;
            });
            
            const percent = maxScore > 0 ? Math.round((score / maxScore) * 100) : 0;
            
            results.push({
                name: cand.name,
                score: score,
                maxScore: maxScore,
                percent: percent
            });
        });
        
        results.sort((a, b) => b.percent - a.percent);
    }
    
    // ✅ Prüfen ob Antworten vorhanden sind
    if (Object.keys(userAnswers).length === 0) {
        alert('Bitte beantworte zuerst die Fragen und werte das Ergebnis aus!');
        return;
    }
    
    // ✅ ERGEBNISSE auf Seite 1
    let yPos = 70;
    doc.setFontSize(16);
    doc.text("Deine Übereinstimmungen:", 20, yPos);
    yPos += 15;
    
    doc.setFontSize(11);
    results.forEach((result, index) => {
        if (yPos > 250) {
            doc.addPage();
            yPos = 25;
        }
        
        doc.setFont(undefined, 'bold');
        doc.text(`${index + 1}. ${result.name}`, 20, yPos);
        
        doc.setFontSize(14);
        doc.text(`${result.percent} %`, 140, yPos);
        
        doc.setFont(undefined, 'normal');
        doc.setFontSize(9);
        doc.text(`(${result.score} / ${result.maxScore} Punkte)`, 165, yPos);
        
        yPos += 10;
        doc.setFontSize(11);
    });
    
    // ✅ TOP-EMPFEHLUNG hervorheben
    yPos += 10;
    if (yPos > 240) {
        doc.addPage();
        yPos = 25;
    }
    
    doc.setFontSize(18);
    doc.setFont(undefined, 'bold');
    doc.text("🎯 Höchste Übereinstimmung:", 20, yPos);
    
    doc.setFontSize(20);
    doc.setTextColor(0, 100, 0);
    doc.text(results[0].name, 20, yPos + 15);
    doc.setTextColor(0, 0, 0);
    
    doc.setFontSize(16);
    doc.setFont(undefined, 'normal');
    doc.text(`mit ${results[0].percent}% Übereinstimmung`, 20, yPos + 28);
    
    // ✅ NEUE SEITE: Deine Antworten
    doc.addPage();
    yPos = 25;
    
    doc.setFontSize(18);
    doc.setFont(undefined, 'bold');
    doc.text("Deine Antworten", 20, yPos);
    yPos += 15;
    
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.text(`Du hast ${Object.keys(userAnswers).length} von ${questions.length} Fragen beantwortet.`, 20, yPos);
    yPos += 15;
    
    let currentTopic = null;
    questions.forEach((q) => {
        if (!userAnswers[q.id] && userAnswers[q.id] !== 0) {
            return;
        }
        
        if (yPos > 260) {
            doc.addPage();
            yPos = 25;
        }
        
        if (q.topic !== currentTopic) {
            currentTopic = q.topic;
            doc.setFont(undefined, 'bold');
            doc.setFontSize(11);
            doc.text(q.topic, 20, yPos);
            yPos += 8;
            doc.setFont(undefined, 'normal');
            doc.setFontSize(9);
        }
        
        const answerText = translateAnswer(userAnswers[q.id]);
        const isImportant = weights[q.id] === 2;
        
        const questionText = q.text.length > 70 ? q.text.substring(0, 67) + '...' : q.text;
        doc.text(`${q.id}: ${questionText}`, 20, yPos);
        
        doc.setFont(undefined, 'bold');
        doc.text(answerText + (isImportant ? ' ⭐' : ''), 190, yPos, { align: 'right' });
        doc.setFont(undefined, 'normal');
        
        yPos += 7;
    });
    
    // ✅ DISCLAIMER + FUßZEILE
    if (yPos > 240) {
        doc.addPage();
        yPos = 25;
    }
    
    yPos += 15;
    doc.setFontSize(9);
    doc.setTextColor(100, 100, 100);
    doc.text("Hinweis: Dies ist ein privates, inoffizielles Informationsangebot.", 20, yPos);
    doc.text("Die Positionen beruhen auf einer eigenen Einschätzung und können fehlerhaft sein.", 20, yPos + 6);
    doc.text("Bitte informiere dich zusätzlich in den Programmen und Auftritten der Kandidierenden.", 20, yPos + 12);
    
    // Fußzeile auf jeder Seite
    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text("passau-ob-wahlomat (privates Projekt)", 20, 290);
        doc.text(`Seite ${i} von ${pageCount}`, 190, 290, { align: 'right' });
    }
    
    doc.save("passau-ob-wahlomat-ergebnis.pdf");
}

function translateAnswer(value) {
    switch(value) {
        case 1: return '✓ Stimme zu';
        case 0: return '~ Neutral';
        case -1: return '✗ Stimme nicht zu';
        default: return 'Übersprungen';
    }
}
</script>
</body>
</html>
