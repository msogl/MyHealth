<div class="center">
    <?= _W($client.
        ', IP: '.(getHostByName(getHostName()) ?? 'unknown').
        ', PHP '.phpversion()
    )?>
</div>