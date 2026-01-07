import { fetchUserInfo } from "../service/axiosService.js"

export const requireBTOCAccess = async (req, res, next) => {
    const token = req.get('DOLAPIKEY')
    if (!token) {
        return res.status(401).send({ status: 'error', message: 'Missing DOLAPIKEY' })
    }

    try {
        const user = await fetchUserInfo(token)
        const btocRights = user?.rights?.btocaccess || {}
        const hasAnyBTOCRight = Object.values(btocRights).some(Boolean)
        const isAdmin = !!(user?.admin || user?.superadmin || user?.rights?.admin)
        const hasAccess = hasAnyBTOCRight || isAdmin

        if (!hasAccess) {
            return res.status(403).send({ status: 'forbidden', message: 'BTOC access right required' })
        }

        req.dolibarrUser = user
        return next()
    } catch (error) {
        console.log('Dolibarr rights check failed ->', error?.response?.data || error.message)
        return res.status(401).send({ status: 'error', message: 'Invalid token or unable to fetch user rights' })
    }
}
