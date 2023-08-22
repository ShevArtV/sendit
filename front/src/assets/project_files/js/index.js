import CustomInputNumber from './modules/custom_input_number.js';
import * as savFunctions from "./modules/functions.js";
import CustomSelect from './modules/custom_select.js';
import IziToast from './modules/msizitoast.class.js';

document.addEventListener('readystatechange', function () {
    if (document.readyState == 'complete') {
        // основной объект со всеми функциями
        const projectScripts = {
            // конфигурация
            navItemSelector: '.nav-item',
            activeClass: 'active',
            ymId: document.querySelector('meta[name="ym_id"]'),
            phoneMaskEls: document.querySelectorAll('input[type="tel"]'),
            antiSpamKeyInput: document.querySelectorAll('input[name="secret"]'),
            navLinks: document.querySelectorAll('.js-nav-link'),
            sections: document.querySelectorAll('.js-section'),
            inputNumbers: document.querySelectorAll('.js-input-number'),
            pageNavs: document.querySelectorAll('[data-id]'),
            anchors: document.querySelectorAll('a[href*="#"]'),
            lg: window.matchMedia('(min-width: 992px)').matches,
            checkboxes: document.querySelectorAll('input[type="checkbox"]'),
            togglers: document.querySelectorAll('.js-toggler'),
            modalTogglers: document.querySelectorAll('[data-modal]'),
            videoModals: document.querySelectorAll('[data-video]'),
            navItems: document.querySelectorAll('.js-nav-item'),
            ranges: document.querySelectorAll('.js-range'),
            authPhoneInput: document.querySelector('.js-auth-phone'),
            codeInputs: document.querySelectorAll('.js-code-input'),
            videoPlayer: document.querySelectorAll('.js-video'),
            lazyLoadElems: document.querySelectorAll('[data-src]'),
            quickFormClass: 'js-quick-order',
            favoritesClass: 'js-favorites',
            msMessageSettingsPath: 'assets/project_files/js/message_settings.json',
            actionUrl: 'assets/project_files/action.php',

            swiperScriptPath: 'assets/project_files/js/modules/swiper-bundle.js',
            swiperStylePath: 'assets/project_files/css/swiper.min.css',

            fancyboxScriptPath: 'assets/project_files/js/modules/fancybox.umd.js',
            fancyboxStylePath: 'assets/project_files/css/fancybox.min.css',

            videoPlayerScriptPath: 'assets/project_files/js/modules/plyr.js',
            videoPlayerStylePath: 'assets/project_files/css/plyr.min.css',

            maskerScriptPath: 'assets/project_files/js/modules/vanilla-masker.js',

            nouisliderStylePath: 'assets/project_files/css/nouislider.css',

            slidersOptions: {
                ".js-reviews-swiper": {
                    slidesPerView: 1,
                    spaceBetween: 10,
                    loop: true,
                    autoHeight: false,
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                    pagination: {
                        el: '.swiper-pagination',
                        type: 'bullets',
                    },
                },
                ".js-team-swiper": {
                    slidesPerView: 1,
                    spaceBetween: 15,
                    loop: false,
                    autoHeight: false,
                    pagination: {
                        el: '.swiper-pagination',
                        type: 'bullets',
                    },
                    breakpoints: {
                        768: {
                            slidesPerView: 2,
                            spaceBetween: 15,
                            navigation: {
                                nextEl: '.swiper-button-next',
                                prevEl: '.swiper-button-prev',
                            },
                        },
                        992: {
                            slidesPerView: 3,
                            spaceBetween: 15
                        }
                    }
                },
            },

            activateMenuItem(anchors) {
                projectScripts.pageNavs.forEach(el => {
                    if (el.closest(projectScripts.navItemSelector)) {
                        el.closest(projectScripts.navItemSelector).classList.remove(projectScripts.activeClass);
                    }
                });
                anchors.forEach(el => {
                    if (el.closest(projectScripts.navItemSelector)) {
                        el.closest(projectScripts.navItemSelector).classList.add(projectScripts.activeClass);
                    }
                });
            },

            modalShow(el) {
                Fancybox.show([
                    {
                        src: el.dataset.modal,
                        type: "inline",

                    }
                ]);
            },

            sendRequest(params) {
                const body = params.body || new FormData()
                const headers = params.headers || {'X-Requested-With': 'XMLHttpRequest'}
                const url = params.url || this.config.actionUrl
                const method = params.method || this.config.formMethod

                let options = {method, headers, body}
                if (method === 'GET') {
                    options = {method, headers}
                }

                return fetch(url, options)
            },

            movingText(sections) {
                sections.forEach(function (el) {
                    const textBlock = el.querySelector('.js-moving-text');
                    if (textBlock) {
                        let direction = textBlock.dataset.direction,
                            elFromTop = textBlock.getBoundingClientRect().top,
                            offset = textBlock.dataset.offset || 50,
                            translateWidth = ((window.innerHeight - elFromTop) / window.innerHeight) * 100;

                        if (direction !== 'right') {
                            textBlock.style.transform = 'translateX(' + (0 - (translateWidth - offset)) + '%)';
                        } else {
                            textBlock.style.transform = 'translateX(' + (translateWidth - offset) + '%)';
                        }
                    }
                });
            },

            sectionObserver(entries, observer){
                entries.forEach((entry) => {
                    entry.target.classList[entry.isIntersecting ? 'add' : 'remove']('js-viewed');
                    document.dispatchEvent(new CustomEvent('visability-change', {detail: {element: entry.target}}));
                });
            }
        };

        async function documentReady() {

            savFunctions.loadScript('assets/project_files/js/modules/sendit.js', () => {
                document.addEventListener('si:init', (e) => {
                    if(typeof SendIt.QuizForm !== 'undefined'){
                        /*setTimeout(() => {
                            const root = document.querySelector('[data-qf-root]');
                            SendIt.QuizForm.change(root, 47, 1);
                        }, 3000);
                        setTimeout(() => {
                            const root = document.querySelector('[data-qf-root]');
                            SendIt.QuizForm.change(root, 2, 1);
                        }, 6000);
                        setTimeout(() => {
                            const root = document.querySelector('[data-qf-root]');
                            SendIt.QuizForm.change(root, 47, 1);
                        }, 9000);*/
                    }
                });
            }, '', 'module');

            // запоминаем тему
            let theme = savFunctions.getCookie('theme');
            if(!theme){
                const hours = new Date().getHours();
                theme = (hours >= 20 || hours < 8) ? 'dark' : '';
            }
            if(theme){
                document.querySelector(':root').classList.add(theme);
            }

            // lazyload
            if (projectScripts.lazyLoadElems) {
                document.addEventListener('visability-change', (e) => {
                    const root = e.detail.element;
                    const lazyLoadAttr = 'data-src';
                    const dataKey = lazyLoadAttr.replace('data-', '');
                    const media = root.querySelectorAll(`[${lazyLoadAttr}]`);
                    if(root.dataset[dataKey]){
                        root.style.backgroundImage = 'url(' + root.dataset[dataKey] + ')';
                    }
                    if(root.classList.contains('js-viewed')){
                        if(media.length){
                            media.forEach(function (elem) {
                                if (['IMG', 'IFRAME', 'VIDEO', 'SOURCE'].includes(elem.tagName)) {
                                    elem.src = elem.dataset[dataKey];
                                    if (elem.closest('.js-video')) {
                                        if (typeof Plyr === 'undefined') {
                                            loadScript('assets/project_files/js/modules/plyr.js', () => {
                                                savFunctions.startPlyr(elem);
                                            }, 'assets/project_files/css/plyr.min.css');
                                        } else {
                                            savFunctions.startPlyr(elem);
                                        }
                                    }
                                } else {
                                    elem.style.backgroundImage = 'url(' + elem.dataset[dataKey] + ')';
                                }
                                elem.removeAttribute(lazyLoadAttr);
                            });
                        }
                        // разворачиваем svg
                        savFunctions.getImgData('.js-svg-img');
                    }
                });
            }

            // разворачиваем svg
            savFunctions.getImgData('.js-svg-img');

            // отслеживаем видимость секций
            if (projectScripts.sections) {
                const observer = new IntersectionObserver(projectScripts.sectionObserver, {
                    rootMargin: '0px',
                    threshold: 0.3
                });
                projectScripts.sections.forEach(el => observer.observe(el));

                projectScripts.movingText(projectScripts.sections);
            }

            // меняем уведомления
            if (typeof miniShop2 !== 'undefined') {
                const response = await projectScripts.sendRequest({url: projectScripts.msMessageSettingsPath, method: 'GET'})
                if (response.ok) {
                    const messageSettings = await response.json();
                    try {
                        miniShop2.Message = new IziToast(messageSettings.MsIziToast);
                    } catch (e) {
                        throw new Error('Failed to import IziToast module');
                    }
                }
            }

            document.addEventListener('click', async (e) => {

            });

            document.addEventListener('submit', (e) => {

            });
            document.addEventListener('change', (e) => {

            });

            // обработка форм
            document.addEventListener('afl_complete', e => {
                const response = e.detail.response;
                const form = e.detail.form;
                console.log(response); // ответ сервера
            });

            // кастомный селект
            const customSelect = new CustomSelect('.js-custom-select');
            if (typeof jQuery !== 'undefined') {
                $(document).on('mse2_load', function (e, data) {
                    new CustomSelect('.js-custom-select');
                });
                $(document).on('pdopage_load', function (e, config, response) {
                    new CustomSelect('.js-custom-select');
                });
            }

            // кастомный input[type=number]
            if (projectScripts.inputNumbers) {
                projectScripts.inputNumbers.forEach(el => {
                    new CustomInputNumber(el, {});
                });
            }

            // обработка клика по якорю
            if (projectScripts.anchors) {
                projectScripts.anchors.forEach(anchor => {
                    if (anchor.getAttribute('href').indexOf('http') == -1 && !anchor.getAttribute('data-bs-toggle')) {
                        anchor.addEventListener('click', (e) => {
                            e.preventDefault();
                            savFunctions.scrollIntoView(anchor);
                        });
                    }
                });
            }

            // переключаем меню
            if (projectScripts.togglers) {
                projectScripts.togglers.forEach(toggler => {
                    toggler.addEventListener('click', (e) => {
                        e.preventDefault();
                        toggler.classList.toggle(projectScripts.activeClass);
                        const targets = document.querySelectorAll(toggler.dataset.target);
                        if (targets.length) {
                            targets.forEach(target => {
                                if(target.tagName !== 'HTML'){
                                    target.classList.toggle(projectScripts.activeClass);
                                }else{
                                    target.classList.toggle('dark');
                                    target.classList.contains('dark') ? savFunctions.setCookie('theme', 'dark') : savFunctions.setCookie('theme', '')
                                }
                            });
                        }
                    });
                });
            }


            // прокручиваем страницу если в адресе есть якорь
            let targetId = document.location.href.match(/#(.*)$/);
            if (targetId) {
                projectScripts.navLinks.forEach(link => {
                    if (targetId[0].indexOf(link.getAttribute('href')) != -1) {
                        setTimeout(() => {
                            savFunctions.scrollIntoView(link);
                        }, 1000);
                    }
                });
            }

            // Маска ввода номера телефона
            /*savFunctions.loadScript(projectScripts.maskerScriptPath, () => {
                for (let i = 0; i < projectScripts.phoneMaskEls.length; i++) {
                    VMasker(projectScripts.phoneMaskEls[i]).maskPattern("9(999)999-99-99");
                    VMasker.toPattern('+7', {pattern: "9(999)999-99-99", placeholder: "x"});
                }
            });*/
            for (let i = 0; i < projectScripts.phoneMaskEls.length; i++) {
                projectScripts.phoneMaskEls[i].addEventListener('keydown', savFunctions.phoneMask);
            }

            // отслеживаем прокрутку
            window.addEventListener('scroll', () => {
                if(projectScripts.sections.length){
                    projectScripts.movingText(projectScripts.sections);
                }
               /* if (projectScripts.sections) {
                    projectScripts.sections.forEach(section => {
                        if (savFunctions.visible(section)) {
                            const anchors = document.querySelectorAll('[data-id="#' + section.id + '"]');
                            if (anchors.length) {
                                projectScripts.activateMenuItem(anchors);
                            }
                        }
                    });
                }*/
            }, {passive: true});

            // fancybox
            if (projectScripts.modalTogglers.length || projectScripts.videoModals.length) {
                savFunctions.loadScript(projectScripts.fancyboxScriptPath, () => {
                    document.addEventListener('click', (e) => {
                        const modalToggler = e.target.dataset.hasOwnProperty('modal') ? e.target : e.target.closest('[data-modal]')
                        const videoToggler = e.target.dataset.hasOwnProperty('video') ? e.target : e.target.closest('[data-video]')
                        if (modalToggler) {
                            projectScripts.modalShow(modalToggler);
                        }
                        if (videoToggler) {
                            e.preventDefault();
                            Fancybox.show([
                                {
                                    src: videoToggler.dataset.src || videoToggler.href,
                                    type: "video"
                                }
                            ]);
                        }
                    });
                }, projectScripts.fancyboxStylePath);
            }

            // swiper
            if (projectScripts.swiperScriptPath && projectScripts.slidersOptions) {
                savFunctions.loadScript(projectScripts.swiperScriptPath, () => {
                    for (let slidersOptionsKey in projectScripts.slidersOptions) {
                        new Swiper(slidersOptionsKey, projectScripts.slidersOptions[slidersOptionsKey]);
                    }
                }, projectScripts.swiperStylePath);
            }

            // plyr
            if (projectScripts.videoPlayerScriptPath && projectScripts.videoPlayer.length) {
                savFunctions.loadScript(projectScripts.videoPlayerScriptPath, () => {
                }, projectScripts.videoPlayerStylePath);
            }
        }

        documentReady();
    }
});