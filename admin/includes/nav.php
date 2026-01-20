<?php
/**
 * Admin Navigation Component
 * Main navigation for admin pages
 */

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$navItems = [
    'dashboard' => 'Dashboard',
    'aframe' => 'A-Frame',
    'c2' => 'C2.js',
    'p5' => 'P5.js',
    'threejs' => 'Three.js'
];

?>
<div class="admin-container">
    <nav class="admin-nav">
        <ul>
            <?php foreach ($navItems as $page => $label): ?>
                <li>
                    <a
                        href="<?php echo url('admin/' . $page . '.php'); ?>"
                        class="<?php echo ($currentPage === $page) ? 'active' : ''; ?>"
                    >
                        <?php echo htmlspecialchars($label); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</div>
