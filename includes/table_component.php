<div class="data-table-container">
    <table class="data-table">
        <thead>
            <tr>
                <?php foreach ($table_headers as $header): ?>
                    <th><?php echo htmlspecialchars($header); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($table_data)): ?>
                <tr>
                    <td colspan="<?php echo count($table_headers); ?>" style="text-align: center; padding: 40px; color: #64748b;">
                        <?php echo htmlspecialchars($table_empty_message ?? 'Aucune donnée trouvée.'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach($table_data as $row): ?>
                <tr>
                    <?php foreach($row as $cell): ?>
                        <td><?php echo $cell; ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
