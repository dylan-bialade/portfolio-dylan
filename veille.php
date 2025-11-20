<?php
$currentPage = 'veille';

// Configuration des flux RSS
$feeds = [
    'symfony' => [
        'label' => 'Symfony / PHP',
        'url' => 'https://symfony.com/blog/rss.xml',
    ],
    'php' => [
        'label' => 'PHP général',
        'url' => 'https://www.php.net/news.rss',
    ],
    'js' => [
        'label' => 'JavaScript',
        'url' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/feed',
    ],
    'java' => [
        'label' => 'Java',
        'url' => 'https://foojay.io/today/feed/',
    ],
    'dotnet' => [
        'label' => '.NET',
        'url' => 'https://devblogs.microsoft.com/dotnet/feed/',
    ],
];

function fetch_feed_items(string $url, string $sourceKey, string $sourceLabel, int $limit = 5): array
{
    $items = [];

    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
            ],
        ]);
        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            return $items;
        }

        $xml = @simplexml_load_string($content);
        if ($xml === false) {
            return $items;
        }

        // Gestion RSS classique (channel->item)
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $i => $item) {
                if ($i >= $limit) {
                    break;
                }
                $items[] = [
                    'title'   => (string) $item->title,
                    'link'    => (string) $item->link,
                    'date'    => isset($item->pubDate) ? (string) $item->pubDate : '',
                    'source'  => $sourceLabel,
                    'sourceKey' => $sourceKey,
                ];
            }
        }
        // Gestion Atom (entry)
        elseif (isset($xml->entry)) {
            foreach ($xml->entry as $i => $entry) {
                if ($i >= $limit) {
                    break;
                }
                $link = '';
                if (isset($entry->link)) {
                    foreach ($entry->link as $l) {
                        $attrs = $l->attributes();
                        if (isset($attrs['href'])) {
                            $link = (string) $attrs['href'];
                            break;
                        }
                    }
                }
                $items[] = [
                    'title'   => (string) $entry->title,
                    'link'    => $link,
                    'date'    => isset($entry->updated) ? (string) $entry->updated : '',
                    'source'  => $sourceLabel,
                    'sourceKey' => $sourceKey,
                ];
            }
        }
    } catch (Throwable $e) {
        // On ignore en cas d'erreur
    }

    return $items;
}

// Récupération de tous les articles
$allItems = [];
foreach ($feeds as $key => $feed) {
    $items = fetch_feed_items($feed['url'], $key, $feed['label'], 5);
    $allItems = array_merge($allItems, $items);
}

// Tri par date si possible
usort($allItems, function ($a, $b) {
    $da = strtotime($a['date'] ?? '') ?: 0;
    $db = strtotime($b['date'] ?? '') ?: 0;
    return $db <=> $da;
});
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Veille technologique – Bialadev Studio</title>
  <meta name="description"
        content="Veille technologique de Bialadev Studio : sélection d’articles récents autour de Symfony, PHP, JavaScript, Java et .NET." />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
  <section class="section">
    <div class="container">
      <h1>Veille technologique</h1>
      <p class="section-intro">
        Cette page regroupe quelques articles récents issus de différents flux RSS : Symfony, PHP,
        JavaScript, Java, .NET… L’objectif est de rester à jour sur les technologies que j’utilise.
      </p>

      <div class="filters">
        <button class="filter-btn active" data-filter="all">Tous</button>
        <?php foreach ($feeds as $key => $feed): ?>
          <button class="filter-btn" data-filter="<?php echo htmlspecialchars($key); ?>">
            <?php echo htmlspecialchars($feed['label']); ?>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="grid veille-grid">
        <?php if (empty($allItems)): ?>
          <p>Aucun article n’a pu être récupéré pour le moment. Les flux peuvent être temporairement indisponibles.</p>
        <?php else: ?>
          <?php foreach ($allItems as $item): ?>
            <article class="card veille-card" data-source="<?php echo htmlspecialchars($item['sourceKey']); ?>">
              <h2 class="veille-title">
                <a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank" rel="noopener noreferrer">
                  <?php echo htmlspecialchars($item['title']); ?>
                </a>
              </h2>
              <p class="veille-meta">
                <?php echo htmlspecialchars($item['source']); ?>
                <?php if (!empty($item['date'])): ?>
                  · <span><?php echo htmlspecialchars(date('d/m/Y', strtotime($item['date']))); ?></span>
                <?php endif; ?>
              </p>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
  (function () {
    var buttons = document.querySelectorAll('.filter-btn');
    var cards = document.querySelectorAll('.veille-card');

    function setFilter(filter) {
      cards.forEach(function (card) {
        var source = card.getAttribute('data-source');
        if (filter === 'all' || source === filter) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    }

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        buttons.forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        setFilter(btn.getAttribute('data-filter'));
      });
    });

    setFilter('all');
  })();
</script>
</body>
</html>
