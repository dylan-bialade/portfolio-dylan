<?php
// ---------- CONFIG DES FLUX ----------

$groups = [
    'php' => [
        'label' => 'PHP / Symfony',
        'feeds' => [
            ['name' => 'Symfony Blog', 'url' => 'https://symfony.com/blog/rss.xml'],
            ['name' => 'PHP.net News', 'url' => 'https://www.php.net/feed.atom'],
        ],
    ],
    'js' => [
        'label' => 'JS / Front',
        'feeds' => [
            ['name' => 'DEV.to – JavaScript', 'url' => 'https://dev.to/feed/tag/javascript'],
        ],
    ],
    'java' => [
        'label' => 'Java / Spring',
        'feeds' => [
            ['name' => 'Baeldung – Spring', 'url' => 'https://feeds.feedburner.com/Baeldung'],
        ],
    ],
    'csharp' => [
        'label' => 'C# / .NET',
        'feeds' => [
            ['name' => '.NET Blog', 'url' => 'https://devblogs.microsoft.com/dotnet/feed/'],
        ],
    ],
    'general' => [
        'label' => 'Général / Culture tech',
        'feeds' => [
            ['name' => 'OpenClassrooms – Tech', 'url' => 'https://blog.openclassrooms.com/fr/category/tech/feed/'],
        ],
    ],
];

// ---------- FONCTION DE RÉCUP DES FLUX ----------

function fetchFeedItems(string $url, string $sourceName, string $topic, int $limit = 5): array
{
    $items = [];

    $context = stream_context_create([
        'http' => ['timeout' => 5],
        'https' => ['timeout' => 5],
    ]);

    $content = @file_get_contents($url, false, $context);
    if ($content === false) {
        return [];
    }

    $xml = @simplexml_load_string($content);
    if (!$xml) {
        return [];
    }

    // RSS classique : <channel><item>
    if (isset($xml->channel->item)) {
        $entries = $xml->channel->item;
    }
    // Atom : <entry>
    elseif (isset($xml->entry)) {
        $entries = $xml->entry;
    } else {
        return [];
    }

    $i = 0;
    foreach ($entries as $entry) {
        if ($i++ >= $limit) break;

        // Titre
        $title = (string)($entry->title ?? '');

        // Lien (gestion RSS / Atom)
        $link = '';
        if (isset($entry->link['href'])) {
            $link = (string)$entry->link['href'];
        } elseif (isset($entry->link)) {
            $link = (string)$entry->link;
        }

        // Description / résumé
        $desc = '';
        if (isset($entry->description)) {
            $desc = (string)$entry->description;
        } elseif (isset($entry->summary)) {
            $desc = (string)$entry->summary;
        } elseif (isset($entry->content)) {
            $desc = (string)$entry->content;
        }

        $desc = strip_tags($desc);
        if (mb_strlen($desc) > 260) {
            $desc = mb_substr($desc, 0, 260) . '…';
        }

        // Date
        $date = '';
        if (isset($entry->pubDate)) {
            $date = (string)$entry->pubDate;
        } elseif (isset($entry->updated)) {
            $date = (string)$entry->updated;
        } elseif (isset($entry->published)) {
            $date = (string)$entry->published;
        }

        $items[] = [
            'source' => $sourceName,
            'title' => $title,
            'link' => $link,
            'description' => $desc,
            'date' => $date,
            'topic' => $topic,
        ];
    }

    return $items;
}

// ---------- RÉCUPÉRATION DE TOUS LES ARTICLES ----------

$allItems = [];

foreach ($groups as $topicKey => $group) {
    foreach ($group['feeds'] as $feed) {
        $items = fetchFeedItems($feed['url'], $feed['name'], $topicKey, 6);
        $allItems = array_merge($allItems, $items);
    }
}

// Tri par date (du plus récent au plus ancien)
usort($allItems, function ($a, $b) {
    $t1 = strtotime($a['date'] ?: 'now');
    $t2 = strtotime($b['date'] ?: 'now');
    return $t2 <=> $t1;
});
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Veille technologique – Bialadev Studio | Symfony, JS, Java, .NET</title>
  <meta name="description"
        content="Page de veille technologique de Bialadev Studio : flux RSS sur PHP/Symfony, JavaScript, Java/Spring, C#/.NET et culture tech." />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="index, follow" />
  <link rel="stylesheet" href="style.css" />
</head>

<body>
  <header class="topbar">
    <div class="container topbar-inner">
      <div class="logo">
        <span class="logo-mark">&lt;/&gt;</span>
        <span class="logo-text">Dylan Bialade</span>
      </div>
      <nav class="nav">
        <a href="index.html" class="nav-link">Accueil</a>
        <a href="projects.html" class="nav-link">Projets</a>
        <a href="veille.php" class="nav-link active">Veille</a>
        <a href="index.html#skills" class="nav-link">Compétences</a>
        <a href="index.html#contact" class="nav-link">Contact</a>
        <a href="contact.php" class="nav-link">Contact</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="section">
      <div class="container">
        <h1 class="page-title">Veille technologique</h1>
        <p class="section-intro">
          Cette page agrège plusieurs <strong>flux RSS</strong> autour de mes technologies
          principales (PHP/Symfony, JavaScript, Java, C#, etc.). L’idée est d’avoir un
          mini-<em>Feedly</em> directement intégré à mon portfolio pour suivre l’actualité
          du développement.
        </p>

        <!-- FILTRES -->
        <div class="filters">
          <button class="filter-btn active" data-filter="all">Tous</button>
          <button class="filter-btn" data-filter="php">PHP / Symfony</button>
          <button class="filter-btn" data-filter="js">JS / Front</button>
          <button class="filter-btn" data-filter="java">Java / Spring</button>
          <button class="filter-btn" data-filter="csharp">C# / .NET</button>
          <button class="filter-btn" data-filter="general">Général</button>
        </div>

        <!-- LISTE DES ARTICLES -->
        <div class="projects-grid">
          <?php if (empty($allItems)): ?>
            <p>Aucun article n’a pu être chargé pour le moment (problème réseau ou flux indisponibles).</p>
          <?php else: ?>
            <?php foreach ($allItems as $item): ?>
              <article class="project-card" data-topic="<?php echo htmlspecialchars($item['topic']); ?>">
                <h3>
                  <a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo htmlspecialchars($item['title']); ?>
                  </a>
                </h3>
                <p class="project-tags">
                  <?php echo htmlspecialchars($item['source']); ?>
                  <?php if (!empty($item['date'])): ?>
                    &nbsp;•&nbsp;
                    <span><?php echo htmlspecialchars($item['date']); ?></span>
                  <?php endif; ?>
                </p>
                <p>
                  <?php echo htmlspecialchars($item['description']); ?>
                </p>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container footer-inner">
      <p>© <span id="year"></span> – Dylan Bialade. Tous droits réservés.</p>
      <p class="footer-note">Veille alimentée automatiquement par des flux RSS.</p>
    </div>
  </footer>

  <script>
    // Année dynamique
    document.getElementById('year').textContent = new Date().getFullYear();

    // Filtrage client (comme pour la page projets)
    const filterButtons = document.querySelectorAll('.filter-btn');
    const cards = document.querySelectorAll('.project-card');

    filterButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const filter = btn.getAttribute('data-filter');

        filterButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        cards.forEach(card => {
          const topic = card.getAttribute('data-topic');
          if (filter === 'all' || topic === filter) {
            card.style.display = '';
          } else {
            card.style.display = 'none';
          }
        });
      });
    });
  </script>
</body>
</html>
