export default function returnConfigs(){
  return {
    QuizForm : {
      pathToScripts: './quizform.js',
      rootSelector: '[data-qf-root]',
      itemSelector: '[data-qf-item]',
      itemCompleteSelector: '[data-qf-complete="1"]',
      finishSelector: '[data-qf-finish]',
      itemCurrentSelector: '[data-qf-current="1"]',
      btnSelector: '[data-qf-btn]',
      btnNextSelector: '[data-qf-btn="next"]',
      btnPrevSelector: '[data-qf-btn="prev"]',
      btnSendSelector: '[data-qf-btn="send"]',
      btnResetSelector: '[data-qf-btn="reset"]',
      nextIndexSelector: '[data-qf-next]',
      progressSelector: '[data-qf-progress]',
      currentQuestionSelector: '[data-qf-page]',
      totalQuestionSelector: '[data-qf-total]',
      pagesSelector: '[data-qf-pages]',
      progressValueSelector: '[data-qf-progress-value]',
      activeClass: 'active',
      visabilityClass: 'v_hidden',
      disabledClass: 'disabled',
      sendEvent: 'si:complete'
    },
    SaveFormData : {
      pathToScripts: './saveformdata.js',
      rootSelector: '[data-si-form]',
      rootKey: 'siForm',
      sendEvent: 'si:complete'
    },
  }
}