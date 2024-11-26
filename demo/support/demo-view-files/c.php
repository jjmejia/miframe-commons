<style>
	.local { background:#f4f4f4;border:1px solid #ccc;padding:10px; }
	.local .hero { background:darkblue; color:#f4f4f4; padding:10px; margin-top:0; }
	.local .hero code { color:#333; }
	.local .mfsd .mfsd-content { background:#fff; }
</style>
<div class="local">
	<p class="hero">Invocando <code>miframe_view('a', ...)</code> para validar comportamiento.</p>
	<?= miframe_view('a', compact('dato1', 'dato2')) ?>
</div>
