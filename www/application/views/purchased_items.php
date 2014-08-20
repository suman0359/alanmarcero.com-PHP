<p>If you encounter a problem while downloading an item, you may contact me at <a href="mailto:{$admin_email}"><?php echo $admin_email; ?></a>.</p>

<?php
    foreach ($items as $item) {
        echo "
            <p>
                <form action='{$item->full_item_url}' method='POST'>
                    <input type='submit' value='Download {$item->name}' class='buttonSubmit' />
                </form>
            </p>
        ";
    }
?>