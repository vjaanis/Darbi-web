<?php
/**
 * Kopīgā galvenā izvēlne (index.php un users.php).
 * Sagaida: $activeView ('', 'jobs', 'exports', 'client-import', 'clients', 'services', 'users'),
 * $isAdmin (bool), $csrf (string), $todayText (?string — ja null, datumu ieraksta JS).
 */
$menuClass = static fn(string $view): string => 'menu-item' . ($activeView === $view ? ' active' : '');
?>
    <header class="topbar">
        <a class="brand" href="./"><span class="brand-icon">D</span>Darbi <small>v<?= Darbi\APP_VERSION ?></small></a>
        <nav class="main-menu" aria-label="Galvenā izvēlne">
            <a class="<?= $menuClass('') ?>" href="./">Jauns darbs</a>
            <a class="<?= $menuClass('jobs') ?>" href="index.php?view=jobs">Pārskats</a>
            <?php if ($isAdmin): ?><a class="<?= $menuClass('exports') ?>" href="index.php?view=exports">Datu eksports</a><?php endif; ?>
            <?php if ($isAdmin): ?><a class="<?= $menuClass('client-import') ?>" href="index.php?view=client-import">Klientu imports</a><?php endif; ?>
            <?php if ($isAdmin): ?><a class="<?= $menuClass('clients') ?>" href="index.php?view=clients">Klienti</a><?php endif; ?>
            <?php if ($isAdmin): ?><a class="<?= $menuClass('services') ?>" href="index.php?view=services">Pakalpojumi</a><?php endif; ?>
            <button class="menu-item" type="button" disabled title="Vēl nav pieejams">Iestatījumi</button>
        </nav>
        <?php if ($isAdmin): ?><a class="<?= $menuClass('users') ?>" href="users.php">Konti</a><?php endif; ?>
        <form method="post" action="logout.php">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button class="menu-item" type="submit">Izrakstīties</button>
        </form>
        <span class="date" id="today"><?= $todayText !== null ? htmlspecialchars($todayText, ENT_QUOTES, 'UTF-8') : '' ?></span>
    </header>
