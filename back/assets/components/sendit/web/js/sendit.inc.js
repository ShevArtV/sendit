export default function returnConfigs() {
    return {
        QuizForm: {
            pathToScripts: './modules/quizform.js?v=43r7h7ddff536rggrefg458jdf',
            rootSelector: '[data-si-form]',
            rootKey: 'siForm',
            itemSelector: '[data-qf-item]',
            itemCompleteSelector: '[data-qf-complete="1"]',
            finishSelector: '[data-qf-finish]',
            itemCurrentSelector: '[data-qf-item="${currentIndex}"]',
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
            sendEvent: 'si:send:finish',
        },
        Sending: {
            pathToScripts: './modules/sending.js?v=5f656345efs',
            rootSelector: '[data-si-form]',
            rootKey: 'siForm',
            presetKey: 'siPreset',
            actionUrl: 'assets/components/sendit/web/action.php',
            antiSpamEvent: 'click',
            eventSelector: '[data-si-event="${eventName}"]',
            errorClass: 'si-error'
        },
        SaveFormData: {
            pathToScripts: './modules/saveformdata.js?v=34443dfdsf5635',
            rootSelector: '[data-si-form]',
            rootKey: 'siForm',
            resetEvent: 'si:send:reset'
        },
        Notify: {
            pathToScripts: './modules/notify.js?v=65gsgf45sfsdf',
            jsPath: 'assets/components/sendit/web/js/lib/izitoast/iziToast.min.js',
            cssPath: 'assets/components/sendit/web/css/lib/izitoast/iziToast.min.css',
            handlerClassName: 'iziToast',
            toastSelector: '.iziToast',
            typeSelectors: {
                success: '.iziToast-color-green',
                info: '.iziToast-color-blue',
                error: '.iziToast-color-red',
                warning: '.iziToast-color-yellow',
            },
            titleSelector: '.iziToast-title',
            handlerOptions: {
                timeout: 2500,
                position: "topCenter"
            }
        },
        FileUploader:{
            pathToScripts: './modules/fileuploader.js?v=5i67hdfgdfgt76t',
        }
    }
}