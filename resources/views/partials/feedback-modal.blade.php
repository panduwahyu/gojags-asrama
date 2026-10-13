<dialog class="feedback-modal" data-feedback-modal aria-modal="true" aria-labelledby="feedback-title" aria-describedby="feedback-message">
    <div class="feedback-icon" data-feedback-icon aria-hidden="true"></div>
    <h2 id="feedback-title" data-feedback-title></h2>
    <p id="feedback-message" data-feedback-message></p>
    <button type="button" class="btn" autofocus>OK</button>
</dialog>
@php($feedbackPayload = ['success' => session('success'), 'errors' => $errors->all()])
<script type="application/json" id="feedback-payload">@json($feedbackPayload)</script>
