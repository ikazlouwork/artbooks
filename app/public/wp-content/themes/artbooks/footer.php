    </main>
    <footer class="ab-footer">
        <div class="ab-container ab-footer-inner">
            <p><?php echo esc_html(date_i18n('Y')); ?> Artbooks Publishing House</p>
            <a href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Browse catalog', 'artbooks'); ?></a>
        </div>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
