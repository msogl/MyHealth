const Account = {
	/**
	 * 
	 * @param {string} accountId 
	 * @param {boolean} active 
	 * @returns 
	 */
  toggleActive: async function(accountId, active) {
    if (typeof active !== 'boolean') {
      return {error: 'Cannot process request'};
    }

    const params = {
      aid: accountId,
      status: (active ? 'enable' : 'disable')
    }

    return await doFetch('post', 'enable-disable-account', params)
  }
}