// Form elements
// Inputs
const form = document.querySelector('#form')
const baseSalary = document.querySelector('#baseSalary')
const afp = document.querySelector('#afp')
const afpPercentage = document.querySelector('#afpPercentage')
const healthForecast = document.querySelector('#healthForecast')
const healthForecastPercentage = document.querySelector('#healthForecastPercentage')
const healthForecastAmount = document.querySelector('#healthForecastAmount')
const healthForecastAmountTypes = document.querySelectorAll('input[name="healthForecastAmountType"]');
const healthForecastAmountTypePesos = document.querySelector('#healthForecastAmountTypePesos')
const healthForecastAmountTypeUF = document.querySelector('#healthForecastAmountTypeUF')

// Containers
const settlementForm = document.querySelector('#settlementForm')
const result = document.querySelector('#result')
const healthForecastPercentageContainer = document.querySelector('#healthForecastPercentageContainer')
const healthForecastAmountTypeContainer = document.querySelector('#healthForecastAmountTypeContainer')
const healthForecastAmountContainer = document.querySelector('#healthForecastAmountContainer')
const collationContainer = document.querySelector('#collationContainer')
const formError = document.querySelector('#formError')

const AFP_FONASA = '122-FONASA'
const AFP_FONASA_PERCENTAGE = 7
const HEALTH_FORECAST_AMOUNT_TYPE_UF = 'uf'
const MIN_BASE_SALARY_FOR_COLLATION = 1200000

// Form setup
const afpData = fetch('/api/afp')
  .then(response => {
    if (!response.ok || response.status !== 200) {
      throw new Error('No se pudo cargar la información, intente nuevamente en unos minutos')
    }

    return response.json()
  })
  .then(afps => {
    console.log({ afps })

    afps.map(({ name, value }) => {
      const option = document.createElement('option')
      option.text = name
      option.value = value
      afp.append(option)
    })

    afp.addEventListener('change', e => {
      const afp = afps.find(afpRate => afpRate.value === e.target.value)
      afpPercentage.value = afp ? afp.rate + '%' : ''
    })
  }).catch(error => {
    // console.log({ error })
    const option = document.createElement('option')
    option.text = 'No se pudo cargar la información, intente nuevamente en unos minutos'
    afp.append(option)
  })

const healthForecastData = fetch('/api/healthForecast')
  .then(response => {
    if (!response.ok || response.status !== 200) {
      throw new Error('No se pudo cargar la información, intente nuevamente en unos minutos')
    }

    return response.json()
  })
  .then(institutions => {
    const institutionsOrdered = institutions.sort((a, b) => a['name'].localeCompare(b['name']))

    institutionsOrdered.map(({ code, name }) => {
      const option = document.createElement('option')
      option.value = `${code}-${name}`
      option.text = name
      healthForecast.append(option)
    })
  }).catch(error => {
    // console.log({ error })
    const option = document.createElement('option')
    option.text = 'No se pudo cargar la información, intente nuevamente en unos minutos'
    healthForecast.append(option)
  })

setInputsEventCurrencyFormat()

// Form events
healthForecast.addEventListener('change', ({
  target
}) => {
  healthForecastPercentageContainer.classList.remove('hide')
  healthForecastAmountTypeContainer.classList.remove('hide')
  healthForecastAmountContainer.classList.remove('hide')

  if (target.value === AFP_FONASA) {
    healthForecastPercentage.value = AFP_FONASA_PERCENTAGE + '%'
    healthForecastAmount.value = ''
    healthForecastAmountTypeContainer.classList.add('hide')
    healthForecastAmountContainer.classList.add('hide')

    healthForecastAmountTypePesos.setAttribute('readonly', 'readonly')
    healthForecastAmountTypePesos.removeAttribute('required')

    healthForecastAmountTypeUF.setAttribute('readonly', 'readonly')
    healthForecastAmountTypeUF.removeAttribute('required')

    healthForecastAmount.setAttribute('readonly', 'readonly')
    healthForecastAmount.removeAttribute('required')
  } else {
    healthForecastAmount.removeAttribute('readonly')
    healthForecastAmount.setAttribute('required', 'required')

    healthForecastAmountTypePesos.removeAttribute('readonly')
    healthForecastAmountTypePesos.setAttribute('required', 'required')

    healthForecastAmountTypeUF.removeAttribute('readonly')
    healthForecastAmountTypeUF.setAttribute('required', 'required')

    healthForecastPercentage.value = ''
    healthForecastPercentageContainer.classList.add('hide')
  }
})

baseSalary.addEventListener('blur', e => {
  const salary = e.target.value.replace(/[^\d]/g, '')

  if (salary >= MIN_BASE_SALARY_FOR_COLLATION) {
    collationContainer.classList.add('hide')
  } else {
    collationContainer.classList.remove('hide')
  }
})

Array.from(healthForecastAmountTypes).map(amountType => {
  amountType.addEventListener('change', ({ target }) => {

    if (target.value === 'pesos') {
      healthForecastAmount.addEventListener('focus', setFocusCurrencyFormat)
      healthForecastAmount.addEventListener('blur', setBlurCurrencyFormat)

      healthForecastAmount.removeEventListener('focus', setFocusCurrencyFormatUf)
      healthForecastAmount.removeEventListener('blur', setBlurCurrencyFormatUf)

    } else if (target.value === 'uf') {
      healthForecastAmount.removeEventListener('focus', setFocusCurrencyFormat)
      healthForecastAmount.removeEventListener('blur', setBlurCurrencyFormat)

      healthForecastAmount.addEventListener('focus', setFocusCurrencyFormatUf)
      healthForecastAmount.addEventListener('blur', setBlurCurrencyFormatUf)
    }
  })
})

// Submit
form.addEventListener('submit', e => {
  e.preventDefault()
  e.stopPropagation()
  if (!form.checkValidity()) {
    form.classList.add('was-validated')
    return
  }

  const formData = new FormData(e.target)

  sendForm(formData)
})

function sendForm(formData) {
  const data = Object.fromEntries(formData.entries())

  showErrorMessage();
  toggleSendButton('pending')
  toggleResult('')

  sleep(2).then(() => {
    fetch('/api/?data=salarySettlement&action=calculate', {
      method: 'POST',
      body: JSON.stringify(data),
      headers: {
        'Content-Type': 'application/json'
      }
    })
      .then(async response => {
        try {
          const result = await response.json()

          return result
        } catch (error) {
          throw new Error('Ha ocurrido un error inesperado, intente nuevamente en unos minutos.')
        }
      })
      .then(result => {
        if (!result.ok) {
          showErrorMessage('No se pudo calcular su liquidación. ' + result.message)
          return
        }

        displaySalarySettlement(result.data)
      }).catch(error => {
        showErrorMessage(error.message)
      }).finally(() => {
        toggleSendButton()
      })
  })
}

function displaySalarySettlement(dataToDisplay = {}) {
  document.querySelector('#resultBaseSalary').textContent = `$${dataToDisplay['baseSalary']}`
  document.querySelector('#resultGratification').textContent = `$${dataToDisplay['gratification']}`
  document.querySelector('#resultTransport').textContent = `$${dataToDisplay['transport']}`
  document.querySelector('#resultCollation').textContent = `$${dataToDisplay['collation']}`
  document.querySelector('#resultTotalAssets').textContent = `$${dataToDisplay['totalAssets']}`

  document.querySelector('#resultAfpName').textContent = `${dataToDisplay['afpName']}`
  document.querySelector('#resultAfpPercentage').textContent = `(${dataToDisplay['afpPercentage']}%)`
  document.querySelector('#resultAfpDiscount').textContent = `$${dataToDisplay['afpDiscount']}`

  document.querySelector('#resultHealthForecastName').textContent = `(${dataToDisplay['healthForecastName']})`
  document.querySelector('#resultHealthForecastDiscount').textContent = `$${dataToDisplay['healthForecastDiscount']}`
  if (dataToDisplay['healthForecastAmountType'] === HEALTH_FORECAST_AMOUNT_TYPE_UF) {
    document.querySelector('#resultHealthForecastAmountAndType').textContent = `(${dataToDisplay['healthForecastAmount']} UF)`
  }
  document.querySelector('#resultIncomeTax').textContent = `$${dataToDisplay['incomeTax']}`
  document.querySelector('#resultTotalDuties').textContent = `$${dataToDisplay['totalDuties']}`

  document.querySelector('#resultNetSalary').textContent = `$${dataToDisplay['netSalary']}`

  toggleResult('show')
}

function showErrorMessage(message = '') {
  if (message.length > 0) {
    formError.classList.add('show')
    formError.classList.remove('hide')
    formError.textContent = message
    return
  }

  formError.textContent = ''
  formError.classList.remove('show')
  formError.classList.add('hide')
}

function toggleSendButton(status = '') {
  const buttonSpinner = document.querySelector('#buttonSpinner')
  const buttonText = document.querySelector('#buttonText')
  if (status === 'pending') {
    buttonSpinner.classList.remove('hide')
    buttonText.textContent = 'Calculando...'
    return
  }

  buttonSpinner.classList.add('hide')
  buttonText.textContent = 'Calcular'
}

function toggleResult(action = 'show') {
  if (action === 'show') {
    result.classList.add('d-flex', 'justify-content-center')
    result.classList.remove('hide')

    result.scrollIntoView({
      behavior: 'smooth'
    })
    return
  }

  result.classList.add('hide')
  result.classList.remove('d-flex', 'justify-content-center')
}

function setCurrencyFormat(number = 0) {
  const chileanPeso = new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
    minimumFractionDigits: 0
  })

  return number.length > 0 ? chileanPeso.format(number) : ''
}

function setInputsEventCurrencyFormat() {
  const inputs = document.querySelectorAll('.formatCurrency')

  Array.from(inputs).map(input => {
    input.addEventListener('blur', setBlurCurrencyFormat)
  })

  Array.from(inputs).map(input => {
    input.addEventListener('focus', setFocusCurrencyFormat)
  })
}

function setBlurCurrencyFormat({ target }) {
  target.value = setCurrencyFormat(target.value.replace(/[^\d]/g, ''))
}

function setFocusCurrencyFormat({ target }) {
  target.value = target.value.replace(/[^\d]/g, '')
}

function setBlurCurrencyFormatUf({ target }) {
  const number = target.value.replace(/[^\d,]/g, '').split(',')

  const input = number[0] ? setCurrencyFormat(number[0]) : setCurrencyFormat(number)

  const formatInput = number[1] ? input.replace('$', '') + ',' + number[1] : input.replace('$', '')

  target.value = formatInput + ' UF'
}

function setFocusCurrencyFormatUf({ target }) {
  target.value = target.value.replace(/[^\d,]/g, '')
}

function sleep(seconds) {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve(true)
    }, seconds * 1000)
  })
}