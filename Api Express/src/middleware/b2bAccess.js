import { fetchUserInfo } from "../service/axiosService.js"

export const requireB2BAccess = async (req, res, next) => {
    const token = req.get('DOLAPIKEY')
    if (!token) {
        return res.status(401).send({ status: 'error', message: 'Missing DOLAPIKEY' })
    }

    try {
        const user = await fetchUserInfo(token)
        const b2bRights = user?.rights?.b2baccess || {}
        const hasAnyB2BRight = Object.values(b2bRights).some(Boolean)
        const isAdmin = !!(user?.admin || user?.superadmin || user?.rights?.admin)
        const hasAccess = hasAnyB2BRight || isAdmin

        if (!hasAccess) {
            return res.status(403).send({ status: 'forbidden', message: 'B2B access right required' })
        }

        req.dolibarrUser = user
        return next()
    } catch (error) {
        console.log('Dolibarr rights check failed ->', error?.response?.data || error.message)
        return res.status(401).send({ status: 'error', message: 'Invalid token or unable to fetch user rights' })
    }
}
