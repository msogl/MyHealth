<div id="main-container-narrow" class="mb-2">
    <div class="main-inner">
        <?php if ($errorMsg != "") { ?>
            <p class="track-message"><?=_W($errorMsg)?></p>
        <?php } else { ?>
            <p>
                <label>
                    Thank you for confirming your email address. Click OK to go to the login page.</label><br />
            </p>
        <?php } ?>

        <p class="submit mt-1">
            <button type="button" class="button" onclick="redirect('login')">OK</button>
        </p>
    </div>
</div>